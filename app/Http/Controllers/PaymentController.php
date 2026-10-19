<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\TenantPayment;
use App\Models\AuditLog;
use App\Notifications\PaymentReceived;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $search = strtolower($request->get('search'));
        $method = $request->get('method');
        
        $query = Payment::with([
                'invoice.contract.room',
                'tenantPayment.tenant.user',
                'recordedByUser',
            ])
            ->orderByDesc('received_at')
            ->orderByDesc('created_at');
        
        // Search by invoice number, tenant name, or reference
        if ($search) {
            $searchPattern = '%'.$search.'%';
            $query->where(function($q) use ($searchPattern) {
                $q->whereHas('invoice', function($q2) use ($searchPattern) {
                    $q2->whereRaw('LOWER(invoice_number) LIKE ?', [$searchPattern]);
                })
                ->orWhereHas('tenantPayment.tenant.user', function($q2) use ($searchPattern) {
                    $q2->whereRaw('LOWER(first_name) LIKE ?', [$searchPattern])
                       ->orWhereRaw('LOWER(middle_name) LIKE ?', [$searchPattern])
                       ->orWhereRaw('LOWER(last_name) LIKE ?', [$searchPattern])
                       ->orWhereRaw('LOWER(CONCAT(first_name, " ", COALESCE(middle_name, ""), " ", last_name)) LIKE ?', [$searchPattern]);
                })
                ->orWhereRaw('LOWER(reference) LIKE ?', [$searchPattern]);
            });
        }
        
        // Filter by payment method
        if ($method && $method !== 'all') {
            $query->where('method', $method);
        }
        
        $payments = $query->paginate(15)->withQueryString();

        return view('payments.index', compact('payments', 'search', 'method'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'invoice_id'        => 'required|exists:invoices,id',
            'tenant_payment_id' => 'nullable|exists:tenant_payments,id',
            'amount'            => 'required|numeric|min:0.01',
            'method'            => 'required|in:cash,bank_transfer,gcash,maya,card,qr_ph',
            'received_at'       => 'required|date',
            'reference'         => 'nullable|string|max:100',
            'notes'             => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($data) {
            $invoice   = Invoice::with('tenantPayments.tenant.user')->findOrFail($data['invoice_id']);
            $tpId      = $data['tenant_payment_id'] ?? null;
            $tenantPay = $tpId ? TenantPayment::find($tpId) : null;

            // Record the payment transaction
            Payment::create([
                'invoice_id'        => $invoice->id,
                'tenant_payment_id' => $tenantPay?->id,
                'amount'            => $data['amount'],
                'method'            => $data['method'],
                'received_at'       => $data['received_at'],
                'reference'         => $data['reference'] ?? null,
                'notes'             => $data['notes'] ?? null,
                'recorded_by_type'  => 'staff',
                'recorded_by_user'  => Auth::id(),
            ]);

            // Update the specific TenantPayment row
            if ($tenantPay) {
                $newTpPaid = $tenantPay->amount_paid + $data['amount'];
                // Paid when amount covers share + carry_over (totalOwed)
                $tpStatus  = $newTpPaid >= $tenantPay->totalOwed() ? 'paid' : $tenantPay->status;

                $tenantPay->update([
                    'amount_paid' => $newTpPaid,
                    'status'      => $tpStatus,
                    'method'      => $data['method'],
                    'paid_at'     => $tpStatus === 'paid' ? now() : $tenantPay->paid_at,
                ]);

                // ── If this is the first invoice and tenant fully paid, update deposit
                if ($tpStatus === 'paid') {
                    $contract     = $invoice->contract;
                    $firstInvoice = \App\Models\Invoice::where('contract_id', $contract->id)
                        ->orderBy('billing_month')
                        ->first();

                    if ($firstInvoice && $firstInvoice->id === $invoice->id) {
                        $tenantDeposit = \App\Models\TenantDeposit::where('contract_id', $contract->id)
                            ->where('tenant_id', $tenantPay->tenant_id)
                            ->first();

                        if ($tenantDeposit && $tenantDeposit->amount_paid < $tenantDeposit->amount_required) {
                            $tenantDeposit->update(['amount_paid' => $tenantDeposit->amount_required]);
                            Log::info('First invoice paid: Updated tenant deposit', [
                                'tenant_id'    => $tenantPay->tenant_id,
                                'deposit_paid' => $tenantDeposit->amount_required,
                            ]);
                        }
                    }

                    // ── Cascade to older invoices via the model method (single source of truth)
                    if (($invoice->carry_over_balance ?? 0) > 0) {
                        $invoice->cascadePaymentToOlderInvoices($tenantPay->tenant_id, $data['method']);
                    }
                }

                // Send payment received notification
                try {
                    $tenant = $tenantPay->tenant;
                    $tenant->user->notify(new PaymentReceived(
                        $invoice, $tenantPay, $data['amount'], $data['method']
                    ));
                } catch (\Exception $e) {
                    Log::warning('PaymentReceived notification failed', ['error' => $e->getMessage()]);
                }
            }

            // Recompute invoice-level status from all tenant payments
            $invoice->load('tenantPayments');
            $invoice->recomputeStatus();

            $tenantName = $tenantPay?->tenant->user->name ?? 'unspecified tenant';
            AuditLog::record('Payment', 'Invoice', $invoice->id,
                "₱{$data['amount']} ({$data['method']}) for {$tenantName} recorded by " . Auth::user()->name);
        });

        return redirect()->route('payments.index')
            ->with('success', 'Payment recorded successfully.');
    }

    /**
     * Delete a payment record (owner only — for removing duplicates).
     * Also recalculates the invoice status after removal.
     */
    public function destroy(Payment $payment)
    {
        abort_unless(auth()->user()->hasRole('owner'), 403, 'Owner only.');

        $invoiceId = $payment->invoice_id;
        $amount    = $payment->amount;
        $ref       = $payment->reference ?? 'no-ref';

        DB::transaction(function () use ($payment, $invoiceId) {
            $payment->delete();

            // Recompute invoice and tenant payment statuses
            $invoice = Invoice::with('tenantPayments')->find($invoiceId);
            if ($invoice) {
                // Subtract the deleted amount from amount_paid
                $totalPaid = Payment::where('invoice_id', $invoiceId)->sum('amount');
                $invoice->update(['amount_paid' => $totalPaid]);
                $invoice->recomputeStatus();
            }
        });

        AuditLog::record('Deleted', 'Payment', $payment->id,
            "Payment of ₱{$amount} (ref: {$ref}) deleted from invoice #{$invoiceId} by " . auth()->user()->name);

        return back()->with('success', "Payment of ₱" . number_format($amount, 2) . " deleted.");
    }
}
