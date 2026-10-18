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
            $successUrl = route('tenant.payment-return', ['status' => 'success', 'invoice' => $invoice->id]);
            $cancelUrl  = route('tenant.payment-return', ['status' => 'failed',  'invoice' => $invoice->id]);

            $link = $this->paymongo->createCheckoutSession(
                $fakeInvoice,
                "Room {$invoice->contract->room->room_number} · {$invoice->billing_month} · Your share",
                $successUrl,
                $cancelUrl
            );

            // Store checkout session id on the TenantPayment row for webhook matching
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

    // ── Staff: generate a PayMongo checkout URL for a tenant to scan ─────

    public function staffQrCheckout(Invoice $invoice, \App\Models\TenantPayment $tenantPayment)
    {
        abort_if($invoice->status === 'void', 422, 'Invoice is void.');
        abort_if($tenantPayment->status === 'paid', 422, 'This tenant has already paid.');
        abort_unless($tenantPayment->invoice_id === $invoice->id, 403);

        if (!config('paymongo.secret_key')) {
            return response()->json(['error' => 'PayMongo is not configured.'], 422);
        }

        $amountDue = $tenantPayment->balanceDue();

        if ($amountDue < 20) {
            return response()->json(['error' => 'Amount is too small for online payment (minimum ₱20).'], 422);
        }

        $fakeInvoice = clone $invoice;
        $fakeInvoice->total_amount = $amountDue;
        $fakeInvoice->amount_paid  = 0;

        try {
            $successUrl = route('invoices.show', $invoice) . '?payment=success';
            $cancelUrl  = route('invoices.show', $invoice) . '?payment=cancelled';

            $link = $this->paymongo->createCheckoutSession(
                $fakeInvoice,
                "Room {$invoice->contract->room->room_number} · {$invoice->billing_month} · {$tenantPayment->tenant->user->name}",
                $successUrl,
                $cancelUrl
            );

            $tenantPayment->update(['paymongo_link_id' => $link['id']]);
            $invoice->update(['paymongo_link_id' => $link['id']]);

            Log::info('Staff QR checkout created', [
                'invoice'   => $invoice->invoice_number,
                'tenant_id' => $tenantPayment->tenant_id,
                'amount'    => $amountDue,
                'link_id'   => $link['id'],
            ]);

            return response()->json([
                'checkout_url' => $link['checkout_url'],
                'link_id'      => $link['id'],
                'amount'       => $amountDue,
            ]);

        } catch (\RuntimeException $e) {
            Log::error('Staff QR checkout error', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Could not generate payment link: ' . $e->getMessage()], 422);
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

        // ── Idempotency check BEFORE the transaction ─────────────────────
        // Use the payment resource ID (pay_xxx) as the unique reference
        $reference = $attrs['id'] ?? $event['resource_id'];

        if ($reference && Payment::where('reference', $reference)->exists()) {
            Log::info('PayMongo webhook: duplicate event skipped (pre-transaction)', ['reference' => $reference]);
            return response()->json(['received' => true, 'skipped' => 'duplicate']);
        }

        // Also guard by checkout session / link ID — prevents double-fire from
        // different event types (payment.paid + checkout_session.payment.paid)
        $linkId = $event['link_id'] ?? null;
        if ($linkId && Payment::where('reference', 'like', "%{$linkId}%")->exists()) {
            Log::info('PayMongo webhook: duplicate skipped via link_id', ['link_id' => $linkId]);
            return response()->json(['received' => true, 'skipped' => 'duplicate_link']);
        }

        DB::transaction(function () use ($invoice, $amountPesos, $method, $attrs, $event, $reference) {

            // Double-check inside transaction (race condition guard)
            if ($reference && Payment::where('reference', $reference)->exists()) {
                Log::info('PayMongo webhook: duplicate event skipped (in-transaction)', ['reference' => $reference]);
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

            // Mark the tenant's personal share as paid (including carry-over)
            // Guard: skip if already paid to prevent double-recording
            if ($tenantPay && $tenantPay->status !== 'paid') {
                $tenantPay->update([
                    'amount_paid' => $tenantPay->totalOwed(), // share + carry_over
                    'status'      => 'paid',
                    'method'      => $method,
                    'paid_at'     => now(),
                ]);

                // ── Cascade to older invoices via the model method (single source of truth)
                if (($invoice->carry_over_balance ?? 0) > 0) {
                    $invoice->cascadePaymentToOlderInvoices($tenantPay->tenant_id, $method);
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

        // Strategy 2: remarks contains invoice_number (set when creating the link/session)
        $remarks = $attrs['remarks'] ?? null;
        if ($remarks) {
            $invoice = Invoice::where('invoice_number', $remarks)->first();
            if ($invoice) {
                Log::info('PayMongo: matched via remarks/invoice_number', ['remarks' => $remarks]);
                return $invoice;
            }
        }

        // Strategy 2b: metadata.invoice_id (set in checkout session)
        $metaInvoiceId = $attrs['metadata']['invoice_id'] ?? null;
        if ($metaInvoiceId) {
            $invoice = Invoice::find($metaInvoiceId);
            if ($invoice) {
                Log::info('PayMongo: matched via metadata.invoice_id', ['invoice_id' => $metaInvoiceId]);
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
