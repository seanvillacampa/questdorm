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
        $payments = Payment::with([
                'invoice.contract.room',
                'tenantPayment.tenant.user',
                'recordedByUser',
            ])
            ->orderByDesc('received_at')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('payments.index', compact('payments'));
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
                $tpStatus  = $newTpPaid >= $tenantPay->share_amount ? 'paid' : $tenantPay->status;

                $tenantPay->update([
                    'amount_paid' => $newTpPaid,
                    'status'      => $tpStatus,
                    'method'      => $data['method'],
                    'paid_at'     => $tpStatus === 'paid' ? now() : $tenantPay->paid_at,
                ]);
                
                // ── Cascade payment backwards if tenant fully paid their share
                // and this invoice had carry_over_balance
                if ($tpStatus === 'paid' && ($invoice->carry_over_balance ?? 0) > 0) {
                    // Get all older unpaid invoices for this contract
                    $olderInvoices = Invoice::where('contract_id', $invoice->contract_id)
                        ->where('billing_month', '<', $invoice->billing_month)
                        ->whereNotIn('status', ['paid', 'void'])
                        ->orderBy('billing_month')
                        ->get();
                    
                    foreach ($olderInvoices as $oldInv) {
                        // Find this tenant's payment record for the old invoice
                        $oldTenantPay = TenantPayment::where('invoice_id', $oldInv->id)
                            ->where('tenant_id', $tenantPay->tenant_id)
                            ->whereIn('status', ['pending', 'partial', 'late', 'partial_late', 'overdue', 'partial_overdue'])
                            ->first();
                        
                        if ($oldTenantPay) {
                            $oldTenantPay->update([
                                'amount_paid' => $oldTenantPay->share_amount,
                                'status'      => 'paid',
                                'method'      => $data['method'],
                                'paid_at'     => now(),
                            ]);
                            
                            // Recompute the old invoice status
                            $oldInv->refresh();
                            $oldInv->recomputeStatus();
                            
                            Log::info('Manual payment: Cascaded to older billing statement', [
                                'old_invoice' => $oldInv->invoice_number,
                                'tenant_id'   => $tenantPay->tenant_id,
                            ]);
                        }
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
}
