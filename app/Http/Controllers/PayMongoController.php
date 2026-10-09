<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PayMongoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayMongoController extends Controller
{
    public function __construct(private PayMongoService $paymongo) {}

    // ── Tenant: create a payment link for their SHARE only ───────────────

    public function createLink(Invoice $invoice)
    {
        $tenant = auth()->user()->tenant;
        abort_unless(
            $tenant && $invoice->contract->tenants->contains('id', $tenant->id),
            403
        );

        abort_if($invoice->status === 'void', 422, 'Cannot pay a voided invoice.');

        // Find this tenant's personal TenantPayment row
        $myPayment = \App\Models\TenantPayment::where('invoice_id', $invoice->id)
            ->where('tenant_id', $tenant->id)
            ->first();

        abort_unless($myPayment, 404, 'No payment record found for this tenant.');
        abort_if($myPayment->status === 'paid', 422, 'Your share is already paid.');

        $amountDue = $myPayment->balanceDue();
        abort_if($amountDue <= 0, 422, 'Your share is already fully paid.');

        if (!config('paymongo.secret_key')) {
            return back()->withErrors(['pay' => 'Online payment is not configured. Please pay in person.']);
        }

        // Create a PayMongo invoice for ONLY this tenant's share
        $fakeInvoice = clone $invoice;
        $fakeInvoice->total_amount = $amountDue;
        $fakeInvoice->amount_paid  = 0;

        try {
            $link = $this->paymongo->createPaymentLink(
                $fakeInvoice,
                "Room {$invoice->contract->room->room_number} · {$invoice->billing_month} · Your share"
            );

            // Store link on the TenantPayment row for webhook matching
            $myPayment->update(['paymongo_link_id' => $link['id']]);
            // Also store on invoice for fallback matching
            $invoice->update(['paymongo_link_id' => $link['id']]);

            Log::info('PayMongo link created for tenant share', [
                'invoice'    => $invoice->invoice_number,
                'tenant_id'  => $tenant->id,
                'amount'     => $amountDue,
                'link_id'    => $link['id'],
            ]);

            return redirect()->away($link['checkout_url']);

        } catch (\RuntimeException $e) {
            Log::error('PayMongo createLink error', ['message' => $e->getMessage()]);
            return back()->withErrors(['pay' => 'Could not create payment link: ' . $e->getMessage()]);
        }
    }

    // ── Webhook: receive payment events from PayMongo ────────────────────

    public function webhook(Request $request)
    {
        // 1. Read raw body FIRST — before any json parsing consumes the stream
        $rawBody   = $request->getContent();
        $sigHeader = $request->header('Paymongo-Signature', '');

        Log::info('PayMongo webhook received', [
            'sig_header_present' => !empty($sigHeader),
            'body_length'        => strlen($rawBody),
        ]);

        // 2. Verify signature
        if (!$this->paymongo->verifyWebhookSignature($rawBody, $sigHeader)) {
            Log::warning('PayMongo webhook: invalid signature — rejecting');
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // 3. Parse body — log full structure for debugging
        $body  = json_decode($rawBody, true);

        Log::info('PayMongo webhook full body', [
            'event_type'  => $body['data']['attributes']['type'] ?? 'unknown',
            'resource_id' => $body['data']['attributes']['data']['id'] ?? null,
            'source'      => $body['data']['attributes']['data']['attributes']['source'] ?? null,
            'remarks'     => $body['data']['attributes']['data']['attributes']['remarks'] ?? null,
            'description' => $body['data']['attributes']['data']['attributes']['description'] ?? null,
        ]);

        $event = $this->paymongo->parseWebhookEvent($body);

        Log::info('PayMongo webhook verified', ['event_type' => $event['event_type']]);

        // 4. Only handle payment success events
        $paymentEvents = [
            'payment.paid',
            'link.payment.paid',
            'checkout_session.payment.paid',
        ];

        if (!in_array($event['event_type'], $paymentEvents)) {
            return response()->json(['received' => true]);
        }

        $attrs = $event['resource_attrs'];

        // 5. Find the invoice — try multiple matching strategies
        $invoice = $this->findInvoice($event, $attrs);

        if (!$invoice) {
            Log::warning('PayMongo webhook: could not match invoice', [
                'event_type'  => $event['event_type'],
                'resource_id' => $event['resource_id'],
                'description' => $attrs['description'] ?? null,
                'remarks'     => $attrs['remarks'] ?? null,
            ]);
            // Still return 200 so PayMongo doesn't keep retrying
            return response()->json(['received' => true, 'warning' => 'invoice not matched']);
        }

        // 6. Record payment atomically — update TenantPayment + recompute invoice
        $amountPesos = ($attrs['amount'] ?? 0) / 100;
        $method      = $this->normalizeMethod($attrs['source']['type'] ?? 'paymongo');

        DB::transaction(function () use ($invoice, $amountPesos, $method, $attrs, $event) {
            $reference = $attrs['id'] ?? $event['resource_id'];

            // Idempotency check
            if ($reference && Payment::where('reference', $reference)->exists()) {
                Log::info('PayMongo webhook: duplicate event skipped', ['reference' => $reference]);
                return;
            }

            // Find the TenantPayment that originated this link
            $linkId    = $event['link_id'] ?? $event['resource_id'] ?? null;
            $tenantPay = $linkId
                ? \App\Models\TenantPayment::where('paymongo_link_id', $linkId)->first()
                : null;

            // Fallback: if link not matched but only one unpaid tenant, mark them
            if (!$tenantPay) {
                $unpaid = \App\Models\TenantPayment::where('invoice_id', $invoice->id)
                    ->where('status', '!=', 'paid')
                    ->get();
                if ($unpaid->count() === 1) {
                    $tenantPay = $unpaid->first();
                    Log::info('PayMongo: TenantPayment matched via single-unpaid fallback', [
                        'tenant_id' => $tenantPay->tenant_id,
                    ]);
                }
            }

            if ($tenantPay) {
                Log::info('PayMongo: TenantPayment found', [
                    'tenant_id' => $tenantPay->tenant_id,
                    'link_id'   => $linkId,
                ]);
            } else {
                Log::warning('PayMongo: TenantPayment NOT found — payment recorded but no tenant linked', [
                    'link_id' => $linkId,
                ]);
            }

            // Mark the tenant's personal share as paid
            if ($tenantPay) {
                $tenantPay->update([
                    'amount_paid' => $tenantPay->share_amount,
                    'status'      => 'paid',
                    'method'      => $method,
                    'paid_at'     => now(),
                ]);
                
                // ── Cascade payment backwards: if this invoice had carry_over_balance,
                // mark all previous unpaid invoices for this tenant as paid too
                if (($invoice->carry_over_balance ?? 0) > 0) {
                    // Get all older unpaid invoices for this contract
                    $olderInvoices = \App\Models\Invoice::where('contract_id', $invoice->contract_id)
                        ->where('billing_month', '<', $invoice->billing_month)
                        ->whereNotIn('status', ['paid', 'void'])
                        ->orderBy('billing_month')
                        ->get();
                    
                    foreach ($olderInvoices as $oldInv) {
                        // Find this tenant's payment record for the old invoice
                        $oldTenantPay = \App\Models\TenantPayment::where('invoice_id', $oldInv->id)
                            ->where('tenant_id', $tenantPay->tenant_id)
                            ->whereIn('status', ['pending', 'partial', 'late', 'partial_late', 'overdue', 'partial_overdue'])
                            ->first();
                        
                        if ($oldTenantPay) {
                            $oldTenantPay->update([
                                'amount_paid' => $oldTenantPay->share_amount,
                                'status'      => 'paid',
                                'method'      => $method,
                                'paid_at'     => now(),
                            ]);
                            
                            // Recompute the old invoice status
                            $oldInv->refresh();
                            $oldInv->recomputeStatus();
                            
                            Log::info('PayMongo: Cascaded payment to older billing statement', [
                                'old_invoice' => $oldInv->invoice_number,
                                'tenant_id'   => $tenantPay->tenant_id,
                            ]);
                        }
                    }
                }
            }

            // Record the payment transaction
            Payment::create([
                'invoice_id'       => $invoice->id,
                'amount'           => $amountPesos,
                'method'           => $method,
                'received_at'      => now()->toDateString(),
                'reference'        => $reference,
                'notes'            => 'PayMongo online payment' . ($tenantPay ? " (tenant #{$tenantPay->tenant_id})" : ''),
                'recorded_by_type' => 'paymongo',
                'recorded_by_user' => null,
            ]);

            // Reload and recompute — fresh query so the just-updated row is included
            $freshInvoice = \App\Models\Invoice::with('tenantPayments')->find($invoice->id);
            $freshInvoice->recomputeStatus();

            AuditLog::record('Payment', 'Invoice', $invoice->id,
                "₱{$amountPesos} via PayMongo ({$method})" .
                ($tenantPay ? "; tenant #{$tenantPay->tenant_id} share paid" : ''));

            Log::info('PayMongo webhook: payment recorded', [
                'invoice'    => $invoice->invoice_number,
                'amount'     => $amountPesos,
                'new_status' => $freshInvoice->status,
                'tenant_pay' => $tenantPay?->id,
            ]);
        });

        return response()->json(['received' => true]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function findInvoice(array $event, array $attrs): ?Invoice
    {
        // Strategy 1: match via TenantPayment.paymongo_link_id (most reliable)
        $linkId = $event['link_id'] ?? null;

        if ($linkId) {
            $tp = \App\Models\TenantPayment::where('paymongo_link_id', $linkId)->first();
            if ($tp) {
                Log::info('PayMongo: matched via TenantPayment.paymongo_link_id', ['link_id' => $linkId, 'tp_id' => $tp->id]);
                return $tp->invoice;
            }

            $invoice = Invoice::where('paymongo_link_id', $linkId)->first();
            if ($invoice) {
                Log::info('PayMongo: matched via Invoice.paymongo_link_id', ['link_id' => $linkId]);
                return $invoice;
            }
        }

        // Strategy 2: remarks contains invoice_number (set when creating the link)
        $remarks = $attrs['remarks'] ?? null;
        if ($remarks) {
            $invoice = Invoice::where('invoice_number', $remarks)->first();
            if ($invoice) {
                Log::info('PayMongo: matched via remarks/invoice_number', ['remarks' => $remarks]);
                return $invoice;
            }
        }

        // Strategy 3: billing statement number in description
        $description = $attrs['description'] ?? '';
        if (preg_match('/BILL-[\w-]+/', $description, $m)) {
            $invoice = Invoice::where('invoice_number', $m[0])->first();
            if ($invoice) {
                Log::info('PayMongo: matched via description regex', ['match' => $m[0]]);
                return $invoice;
            }
        }

        // Strategy 4: match by exact amount against recent unpaid invoices
        // (last-resort fallback — only reliable if amounts are unique)
        $amountPesos = ($attrs['amount'] ?? 0) / 100;
        if ($amountPesos > 0) {
            $invoice = Invoice::whereNotIn('status', ['paid', 'void'])
                ->where('total_amount', $amountPesos)
                ->where('created_at', '>=', now()->subDays(60))
                ->latest()
                ->first();
            if ($invoice) {
                Log::info('PayMongo: matched via amount fallback', ['amount' => $amountPesos, 'invoice' => $invoice->invoice_number]);
                return $invoice;
            }
        }

        Log::warning('PayMongo: could not match billing statement with any strategy', [
            'link_id'     => $linkId,
            'remarks'     => $remarks ?? null,
            'description' => $description,
            'amount'      => $amountPesos,
        ]);

        return null;
    }

    private function normalizeMethod(string $type): string
    {
        return match(strtolower($type)) {
            'gcash'                           => 'gcash',
            'paymaya', 'maya'                 => 'maya',
            'card', 'credit_card', 'e-wallet' => 'card',
            'qrph', 'qr_ph'                   => 'qr_ph',
            default                           => 'paymongo',
        };
    }
}
