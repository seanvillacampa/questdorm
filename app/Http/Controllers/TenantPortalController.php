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
        return Auth::user()->tenant?->contracts()
            ->where('status', 'active')
            ->with(['room', 'tenants.user'])
            ->first();
    }

    public function dashboard()
    {
        return redirect()->route('tenant.bill');
    }

    public function bill()
    {
        $contract = $this->activeContract();
        $invoice  = null;
        $myPayment = null; // TenantPayment row for this tenant
        $reading  = null;

        if ($contract) {
            $myTenantId = Auth::user()->tenant?->id;
            
            // First, try to get an unpaid invoice for the current tenant
            if ($myTenantId) {
                $unpaidInvoice = Invoice::where('contract_id', $contract->id)
                    ->whereHas('tenantPayments', function($q) use ($myTenantId) {
                        $q->where('tenant_id', $myTenantId)
                          ->whereIn('status', ['pending', 'partial', 'overdue', 'partial_overdue']);
                    })
                    ->orderByDesc('billing_month')
                    ->with([
                        'tenantPayments.tenant.user',
                        'payments',
                    ])
                    ->first();
                
                if ($unpaidInvoice) {
                    $invoice = $unpaidInvoice;
                }
            }
            
            // If no unpaid invoice, show current month's invoice (if exists)
            if (!$invoice) {
                $invoice = Invoice::where('contract_id', $contract->id)
                    ->where('billing_month', now()->format('Y-m'))
                    ->with([
                        'tenantPayments.tenant.user',
                        'payments',
                    ])
                    ->first();
            }

            if ($invoice) {
                $myPayment = $invoice->tenantPayments
                    ->firstWhere('tenant_id', $myTenantId);
                
                // Get meter reading for the invoice's billing month
                $reading = MeterReading::where('room_id', $contract->room_id)
                    ->where('reading_month', $invoice->billing_month)
                    ->first();
            }
        }

        return view('tenant.bill', compact('contract', 'invoice', 'myPayment', 'reading'));
    }

    public function payments()
    {
        $contract = $this->activeContract();
        $payments = collect();
        $myTenantId = Auth::user()->tenant?->id;

        if ($contract && $myTenantId) {
            $payments = TenantPayment::where('tenant_id', $myTenantId)
                ->whereHas('invoice', fn ($q) => $q->where('contract_id', $contract->id))
                ->with('invoice')
                ->join('invoices', 'tenant_payments.invoice_id', '=', 'invoices.id')
                ->orderByDesc('invoices.billing_month')
                ->select('tenant_payments.*') // Ensure we only select tenant_payments columns
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
