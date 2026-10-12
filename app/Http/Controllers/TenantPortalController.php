<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\MeterReading;
use App\Models\Payment;
use App\Models\TenantPayment;
use App\Models\DepositEntry;
use Illuminate\Support\Facades\Auth;

class TenantPortalController extends Controller
{
    private function activeContract(): ?Contract
    {
        $tenant = Auth::user()->tenant;
        
        if (!$tenant) {
            return null;
        }
        
        return $tenant->contracts()
            ->where('is_active', true)
            ->wherePivot('left_on', null)
            ->with(['room', 'activeTenants.user'])
            ->first();
    }

    public function dashboard()
    {
        return redirect()->route('tenant.bill');
    }

    public function paymentReturn()
    {
        $contract   = $this->activeContract();
        $myTenantId = Auth::user()->tenant?->id;
        $isPaid     = false;

        if ($contract && $myTenantId) {
            // Recompute statuses so we reflect the latest payment
            Invoice::where('contract_id', $contract->id)
                ->whereNotIn('status', ['paid', 'void'])
                ->with('tenantPayments')
                ->get()
                ->each(fn ($inv) => $inv->recomputeStatus());

            // Check if this tenant's latest invoice is now paid
            $latestPayment = \App\Models\TenantPayment::where('tenant_id', $myTenantId)
                ->whereHas('invoice', fn ($q) => $q->where('contract_id', $contract->id))
                ->orderByDesc('invoice_id')
                ->first();

            $isPaid = $latestPayment?->status === 'paid';
        }

        return view('tenant.payment-return', compact('isPaid'));
    }

    public function bill()
    {
        $contract  = $this->activeContract();
        $invoice   = null;
        $myPayment = null;
        $reading   = null;

        if ($contract) {
            $myTenantId = Auth::user()->tenant?->id;

            // ── Auto-recompute stale statuses for this contract ───────────
            if ($myTenantId) {
                Invoice::where('contract_id', $contract->id)
                    ->whereNotIn('status', ['paid', 'void'])
                    ->where('due_date', '<', now()->startOfDay())
                    ->with('tenantPayments')
                    ->get()
                    ->each(fn ($inv) => $inv->recomputeStatus());
            }

            // ── Auto-cascade: if tenant has a paid invoice with carry-over,
            // ensure all older invoices are also marked paid. This catches
            // cases where payment was made before the cascade logic was deployed.
            if ($myTenantId) {
                $paidWithCarryOver = Invoice::where('contract_id', $contract->id)
                    ->where('carry_over_balance', '>', 0)
                    ->whereHas('tenantPayments', fn ($q) => $q
                        ->where('tenant_id', $myTenantId)
                        ->where('status', 'paid')
                    )
                    ->orderByDesc('billing_month')
                    ->first();

                if ($paidWithCarryOver) {
                    // Check if any older invoices are still unpaid for this tenant
                    $hasOldUnpaid = TenantPayment::where('tenant_id', $myTenantId)
                        ->whereHas('invoice', fn ($q) => $q
                            ->where('contract_id', $contract->id)
                            ->where('billing_month', '<', $paidWithCarryOver->billing_month)
                            ->whereNotIn('status', ['void'])
                        )
                        ->whereIn('status', ['pending', 'overdue', 'partial', 'late', 'partial_late', 'partial_overdue'])
                        ->exists();

                    if ($hasOldUnpaid) {
                        $paidWithCarryOver->cascadePaymentToOlderInvoices($myTenantId);
                    }
                }
            }

            // ── Invoice selection priority ────────────────────────────────
            // 1. Current month's invoice (always preferred — if paid it shows paid)
            // 2. Most recent unpaid invoice (if no current month invoice exists yet)
            // 3. Most recent invoice of any status (last resort)

            $currentMonth = now()->format('Y-m');

            $invoice = Invoice::where('contract_id', $contract->id)
                ->where('billing_month', $currentMonth)
                ->with(['tenantPayments.tenant.user', 'payments'])
                ->first();

            if (!$invoice && $myTenantId) {
                // No current-month invoice yet — show the latest unpaid one
                $invoice = Invoice::where('contract_id', $contract->id)
                    ->whereHas('tenantPayments', function ($q) use ($myTenantId) {
                        $q->where('tenant_id', $myTenantId)
                          ->whereIn('status', ['pending', 'overdue']);
                    })
                    ->orderByDesc('billing_month')
                    ->with(['tenantPayments.tenant.user', 'payments'])
                    ->first();
            }

            if (!$invoice) {
                // Absolute fallback — just show the most recent invoice
                $invoice = Invoice::where('contract_id', $contract->id)
                    ->orderByDesc('billing_month')
                    ->with(['tenantPayments.tenant.user', 'payments'])
                    ->first();
            }

            if ($invoice) {
                $myPayment = $invoice->tenantPayments
                    ->firstWhere('tenant_id', $myTenantId);

                $reading = MeterReading::where('room_id', $contract->room_id)
                    ->where('reading_month', $invoice->billing_month)
                    ->first();
            }
        }

        return view('tenant.bill', compact('contract', 'invoice', 'myPayment', 'reading'));
    }

    public function payments()
    {
        $contract   = $this->activeContract();
        $payments   = collect();
        $myTenantId = Auth::user()->tenant?->id;

        if ($contract && $myTenantId) {
            // Auto-cascade: ensure any paid invoice with carry-over has cascaded
            // to older invoices (fixes payments made before cascade logic was deployed)
            $paidWithCarryOver = Invoice::where('contract_id', $contract->id)
                ->where('carry_over_balance', '>', 0)
                ->whereHas('tenantPayments', fn ($q) => $q
                    ->where('tenant_id', $myTenantId)
                    ->where('status', 'paid')
                )
                ->orderByDesc('billing_month')
                ->first();

            if ($paidWithCarryOver) {
                $hasOldUnpaid = TenantPayment::where('tenant_id', $myTenantId)
                    ->whereHas('invoice', fn ($q) => $q
                        ->where('contract_id', $contract->id)
                        ->where('billing_month', '<', $paidWithCarryOver->billing_month)
                        ->whereNotIn('status', ['void'])
                    )
                    ->whereIn('status', ['pending', 'overdue', 'partial', 'late', 'partial_late', 'partial_overdue'])
                    ->exists();

                if ($hasOldUnpaid) {
                    $paidWithCarryOver->cascadePaymentToOlderInvoices($myTenantId);
                }
            }

            $payments = TenantPayment::where('tenant_id', $myTenantId)
                ->whereHas('invoice', fn ($q) => $q->where('contract_id', $contract->id))
                ->with('invoice')
                ->join('invoices', 'tenant_payments.invoice_id', '=', 'invoices.id')
                ->orderByDesc('invoices.billing_month')
                ->select('tenant_payments.*')
                ->paginate(10);
        }

        return view('tenant.payments', compact('contract', 'payments'));
    }

    public function deposit()
    {
        $contract = $this->activeContract();
        $entries  = collect();
        $depositBalance = 0;
        $myDeposit = null;
        $myTenantId = Auth::user()->tenant?->id;

        if ($contract) {
            // Auto-fix deposit if needed
            $this->autoFixDeposit($contract, $myTenantId);
            
            $entries = DepositEntry::where('contract_id', $contract->id)
                ->orderBy('date')
                ->get();
            
            $depositBalance = $contract->depositBalance();
            
            // Get this tenant's individual deposit record
            if ($myTenantId) {
                $myDeposit = \App\Models\TenantDeposit::where('contract_id', $contract->id)
                    ->where('tenant_id', $myTenantId)
                    ->first();
            }
        }

        return view('tenant.deposit', compact('contract', 'entries', 'depositBalance', 'myDeposit'));
    }
    
    /**
     * Auto-fix deposit record if first invoice is paid but deposit shows unpaid
     */
    private function autoFixDeposit($contract, $tenantId)
    {
        if (!$contract || !$tenantId) {
            return;
        }
        
        $firstInvoice = $contract->invoices()->orderBy('billing_month')->first();
        
        if (!$firstInvoice) {
            return;
        }
        
        // Check if this tenant paid their share of the first invoice
        $tenantPayment = TenantPayment::where('invoice_id', $firstInvoice->id)
            ->where('tenant_id', $tenantId)
            ->where('status', 'paid')
            ->first();
        
        if ($tenantPayment) {
            // Update deposit if needed
            $deposit = \App\Models\TenantDeposit::where('contract_id', $contract->id)
                ->where('tenant_id', $tenantId)
                ->first();
            
            if ($deposit && $deposit->amount_paid < $deposit->amount_required) {
                $deposit->update(['amount_paid' => $deposit->amount_required]);
            }
        }
    }

    public function contract()
    {
        $contract = $this->activeContract();
        return view('tenant.contract', compact('contract'));
    }

    public function messageForm()
    {
        $contract = $this->activeContract();
        return view('tenant.message', compact('contract'));
    }

    public function sendMessage(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
        ]);

        $tenant = Auth::user()->tenant;
        $contract = $this->activeContract();

        if (!$tenant) {
            return back()->withErrors(['error' => 'You must be a tenant to send messages.']);
        }

        \App\Models\TenantMessage::create([
            'tenant_id' => $tenant->id,
            'room_id' => $contract?->room_id,
            'subject' => $request->subject,
            'message' => $request->message,
            'status' => 'unread',
        ]);

        return redirect()->route('tenant.messages')->with('success', 'Your message has been sent to the office. They will respond soon.');
    }

    /**
     * Display tenant's message inbox
     */
    public function messages()
    {
        $tenant = Auth::user()->tenant;
        
        if (!$tenant) {
            return redirect()->route('tenant.dashboard')->withErrors(['error' => 'Tenant profile not found.']);
        }

        $messages = \App\Models\TenantMessage::where('tenant_id', $tenant->id)
            ->with(['room', 'tenant.user', 'replies.user', 'resolvedBy'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('tenant.messages', compact('messages'));
    }

    /**
     * Display a specific message thread
     */
    public function showMessage(\App\Models\TenantMessage $message)
    {
        $tenant = Auth::user()->tenant;
        
        // Ensure tenant can only view their own messages
        if (!$tenant || $message->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to this message.');
        }

        $message->load(['room', 'tenant.user', 'replies.user', 'resolvedBy']);
        
        // Mark unread replies from staff as read
        $message->replies()
            ->whereNull('read_at')
            ->where('user_id', '!=', Auth::id())
            ->update(['read_at' => now()]);

        return view('tenant.message-thread', compact('message'));
    }

    /**
     * Post a reply to a message thread
     */
    public function replyToMessage(\Illuminate\Http\Request $request, \App\Models\TenantMessage $message)
    {
        $tenant = Auth::user()->tenant;
        
        // Ensure tenant can only reply to their own messages
        if (!$tenant || $message->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to this message.');
        }

        // Cannot reply to resolved messages
        if ($message->isResolved()) {
            return back()->with('error', 'Cannot reply to a resolved conversation.');
        }

        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $reply = $message->addReply(Auth::user(), $request->message);

        // Send email notification to all staff (owner and employees)
        try {
            $staffUsers = \App\Models\User::role(['owner', 'employee'])->get();
            foreach ($staffUsers as $staffUser) {
                $staffUser->notify(new \App\Notifications\TenantMessageReplyNotification(
                    $message,
                    $reply,
                    Auth::user()
                ));
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send tenant reply notification', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }

        return back()->with('success', 'Your reply has been sent.');
    }
}
