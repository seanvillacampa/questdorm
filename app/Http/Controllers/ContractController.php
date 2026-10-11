<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\DepositEntry;
use App\Models\Invoice;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\TenantPayment;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $query = Contract::with(['room', 'tenants.user'])
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $s = '%'.$request->search.'%';
            $query->where(fn ($q) =>
                $q->whereRaw("LPAD(id, 4, '0') LIKE ?", [$s])
                  ->orWhereHas('room', fn ($r) => $r->where('room_number', 'like', $s))
            );
        }

        $contracts = $query->paginate(10)->withQueryString();
        return view('contracts.index', compact('contracts'));
    }

    public function create()
    {
        $rooms   = Room::where('status', 'vacant')->orderBy('room_number')->get();
        $tenants = Tenant::with('user')
            ->whereDoesntHave('contracts', fn ($q) => $q->where('is_active', true))
            ->get()
            ->sortBy('user.name');
        return view('contracts.create', compact('rooms', 'tenants'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'room_id'          => 'required|exists:rooms,id',
            'tenant_ids'       => 'required|array|min:1',
            'tenant_ids.*'     => 'exists:tenants,id',
            'start_date'       => 'required|date',
            'due_day'          => 'required|integer|min:1|max:28',
        ]);

        $room = Room::findOrFail($data['room_id']);

        if (count($data['tenant_ids']) > (int) $room->capacity) {
            return back()->withErrors([
                'tenant_ids' => "Room {$room->room_number} has a capacity of {$room->capacity}. You selected " . count($data['tenant_ids']) . " tenant(s).",
            ])->withInput();
        }

        DB::beginTransaction();
        try {
            $contract = Contract::create([
                'room_id'           => $data['room_id'],
                'start_date'        => $data['start_date'],
                'end_date'          => null,
                'due_day'           => $data['due_day'],
                'deposit_collected' => 0,
                'status'            => 'active',
                'is_active'         => true,
                'created_by'        => Auth::id(),
            ]);

            $contract->tenants()->attach(
                collect($data['tenant_ids'])->mapWithKeys(fn ($tid) => [
                    $tid => ['joined_on' => $data['start_date'], 'left_on' => null],
                ])->toArray()
            );

            // Get billing rules for deposit and advance
            $billingRules = \App\Models\Setting::billingRules();
            $advanceMonths = $billingRules['advance_months'];
            $depositMonths = $billingRules['deposit_months'];
            
            $tenantCount = count($data['tenant_ids']);
            $monthlyRent = $room->monthly_rate;
            
            // Calculate deposit required (e.g., 2 months rent)
            $totalDepositRequired = $monthlyRent * $depositMonths;
            $depositPerTenant = round($totalDepositRequired / $tenantCount, 2);

            // Create TenantDeposit records for each tenant
            foreach ($data['tenant_ids'] as $tenantId) {
                \App\Models\TenantDeposit::create([
                    'contract_id'     => $contract->id,
                    'tenant_id'       => $tenantId,
                    'amount_required' => $depositPerTenant,
                    'amount_paid'     => 0,
                    'amount_deducted' => 0,
                    'amount_refunded' => 0,
                ]);
            }

            // Create first billing statement with advance rent + deposit
            $startDate = \Carbon\Carbon::parse($data['start_date']);
            $billingMonth = $startDate->format('Y-m');
            $dueDate = $startDate->copy()->day($data['due_day']);
            
            // If start date is after due day, set due date to next month
            if ($startDate->day > $data['due_day']) {
                $dueDate->addMonth();
            }
            
            $invoiceNumber = 'BILL-' . str_replace('-', '', $billingMonth) . '-' . str_pad($room->room_number, 3, '0', STR_PAD_LEFT);
            
            // Total amount: advance rent (1 month) + deposit (2 months)
            $advanceRent = $monthlyRent * $advanceMonths;
            $totalAmount = $advanceRent + $totalDepositRequired;
            
            $invoice = Invoice::create([
                'invoice_number'     => $invoiceNumber,
                'contract_id'        => $contract->id,
                'tenant_count'       => $tenantCount,
                'billing_month'      => $billingMonth,
                'due_date'           => $dueDate,
                'rent_amount'        => $advanceRent,
                'electricity_amount' => 0,
                'carry_over_balance' => 0,
                'credit_balance'     => 0,
                'total_amount'       => $totalAmount,
                'amount_paid'        => 0,
                'status'             => 'pending',
                'paid_at'            => null,
                'created_by'         => Auth::id(),
            ]);
            
            // Create tenant payments (split evenly)
            $rentPerTenant = round($advanceRent / $tenantCount, 2);
            $totalPerTenant = $rentPerTenant + $depositPerTenant;
            
            foreach ($data['tenant_ids'] as $tenantId) {
                TenantPayment::create([
                    'invoice_id'          => $invoice->id,
                    'tenant_id'           => $tenantId,
                    'share_amount'        => $totalPerTenant,
                    'carry_over_balance'  => 0,
                    'amount_paid'         => 0,
                    'status'              => 'pending',
                    'method'              => null,
                    'paid_at'             => null,
                ]);
            }

            Room::where('id', $data['room_id'])->update(['status' => 'occupied']);

            AuditLog::record('Created', 'Contract', $contract->id,
                "Contract for Room {$room->room_number} — {$tenantCount} tenant(s). First bill: ₱" . number_format($advanceRent, 2) . " advance + ₱" . number_format($totalDepositRequired, 2) . " deposit = ₱" . number_format($totalAmount, 2) . " (₱" . number_format($totalPerTenant, 2) . "/tenant)");

            DB::commit();

            return redirect()->route('contracts.show', $contract)
                ->with('success', "Contract created. First billing statement generated: ₱" . number_format($totalPerTenant, 2) . " per tenant ({$advanceMonths} month advance + {$depositMonths} month deposit).");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create contract: ' . $e->getMessage()])->withInput();
        }
    }

    public function show(Contract $contract)
    {
        $contract->load(['room', 'tenants.user', 'invoices.tenantPayments.tenant.user', 'createdBy', 'depositEntries.recordedBy']);

        $tenantCount   = $contract->tenants->count();
        $rentPerTenant = $tenantCount > 0
            ? round($contract->room->monthly_rate / $tenantCount, 2)
            : $contract->room->monthly_rate;

        $depositBalance = $contract->depositBalance();

        return view('contracts.show', compact('contract', 'rentPerTenant', 'depositBalance'));
    }

    public function edit(Contract $contract)
    {
        abort_if(!$contract->is_active, 422, 'Cannot edit inactive contract.');
        
        return view('contracts.edit', compact('contract'));
    }

    public function update(Request $request, Contract $contract)
    {
        abort_if(!$contract->is_active, 422, 'Cannot edit inactive contract.');
        
        $data = $request->validate([
            'due_day' => 'required|integer|min:1|max:28',
            'new_monthly_rate' => 'nullable|numeric|min:0',
            'effective_month' => 'nullable|required_with:new_monthly_rate|date_format:Y-m',
        ]);

        $changes = [];

        // Update due day if changed
        if ($data['due_day'] != $contract->due_day) {
            $oldDueDay = $contract->due_day;
            $contract->update(['due_day' => $data['due_day']]);
            $changes[] = "Due day changed from {$oldDueDay} to {$data['due_day']}";
        }

        // Update monthly rent if provided
        if (!empty($data['new_monthly_rate']) && !empty($data['effective_month'])) {
            $oldRate = $contract->room->monthly_rate;
            $newRate = $data['new_monthly_rate'];
            $effectiveMonth = $data['effective_month'];

            if ($newRate != $oldRate) {
                $contract->room->update(['monthly_rate' => $newRate]);
                
                // Also update deposit_required to match (typically same as monthly rate or 2x)
                $billingRules = \App\Models\Setting::billingRules();
                $depositMonths = $billingRules['deposit_months'];
                $contract->room->update(['deposit_required' => $newRate * $depositMonths]);
                
                $changes[] = "Monthly rent changed from ₱" . number_format($oldRate, 2) . " to ₱" . number_format($newRate, 2) . " effective " . now()->parse($effectiveMonth)->format('F Y');
                
                AuditLog::record('Updated', 'Contract', $contract->id,
                    "Monthly rent changed from ₱" . number_format($oldRate, 2) . " to ₱" . number_format($newRate, 2) . " for Room {$contract->room->room_number}, effective {$effectiveMonth}");
            }
        }

        if (empty($changes)) {
            return back()->with('info', 'No changes were made.');
        }

        $message = 'Contract updated: ' . implode(', ', $changes);
        
        return redirect()->route('contracts.show', $contract)->with('success', $message);
    }

    /**
     * Deactivate a contract and remove all related data:
     * - Void all unpaid invoices
     * - Delete tenant payment rows for voided invoices
     * - Mark room as vacant
     * - Mark contract is_active = false
     */
    public function deactivate(Contract $contract)
    {
        abort_if(!$contract->is_active, 422, 'Contract is already inactive.');

        DB::transaction(function () use ($contract) {
            // Void all unpaid invoices for this contract
            $unpaidInvoiceIds = Invoice::where('contract_id', $contract->id)
                ->whereNotIn('status', ['paid', 'void'])
                ->pluck('id');

            if ($unpaidInvoiceIds->isNotEmpty()) {
                Invoice::whereIn('id', $unpaidInvoiceIds)->update(['status' => 'void']);
                TenantPayment::whereIn('invoice_id', $unpaidInvoiceIds)
                    ->whereNotIn('status', ['paid'])
                    ->update(['status' => 'void']);
            }

            // Mark room as vacant
            $contract->room->update(['status' => 'vacant']);

            // Deactivate contract
            $contract->update([
                'is_active' => false,
                'status'    => 'ended',
            ]);

            AuditLog::record('Deactivated', 'Contract', $contract->id,
                "Contract for Room {$contract->room->room_number} deactivated. {$unpaidInvoiceIds->count()} unpaid invoice(s) voided.");
        });

        return redirect()->route('contracts.index')
            ->with('success', "Contract CT-" . str_pad($contract->id, 4, '0', STR_PAD_LEFT) . " deactivated. Room {$contract->room->room_number} is now vacant.");
    }

    /**
     * Store a deposit deduction for damages or other reasons.
     */
    public function deductDeposit(Request $request, Contract $contract)
    {
        $data = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'amount'    => 'required|numeric|min:0.01',
            'reason'    => 'required|string|max:500',
            'date'      => 'required|date',
        ]);

        // Find the tenant's deposit record
        $tenantDeposit = \App\Models\TenantDeposit::where('contract_id', $contract->id)
            ->where('tenant_id', $data['tenant_id'])
            ->firstOrFail();

        $tenantBalance = $tenantDeposit->balance();

        if ($data['amount'] > $tenantBalance) {
            $tenant = \App\Models\Tenant::findOrFail($data['tenant_id']);
            return back()->withErrors([
                'amount' => "Cannot deduct ₱" . number_format($data['amount'], 2) . ". {$tenant->user->name}'s deposit balance is only ₱" . number_format($tenantBalance, 2) . ".",
            ])->withInput();
        }

        // Use the TenantDeposit model's deduct method
        $tenantDeposit->deduct($data['amount'], $data['reason']);

        // Also create a DepositEntry for backward compatibility and history tracking
        $entry = DepositEntry::create([
            'contract_id' => $contract->id,
            'type'        => 'deduction',
            'amount'      => $data['amount'],
            'date'        => $data['date'],
            'reason'      => $data['reason'],
            'recorded_by' => Auth::id(),
        ]);

        $tenant = \App\Models\Tenant::findOrFail($data['tenant_id']);
        AuditLog::record('Deposit Deduction', 'Contract', $contract->id,
            "Deducted ₱" . number_format($data['amount'], 2) . " from {$tenant->user->name}'s deposit. Reason: {$data['reason']}");

        return redirect()->route('contracts.show', $contract)
            ->with('success', "Deducted ₱" . number_format($data['amount'], 2) . " from {$tenant->user->name}'s deposit. Their remaining balance: ₱" . number_format($tenantDeposit->balance(), 2));
    }
}
