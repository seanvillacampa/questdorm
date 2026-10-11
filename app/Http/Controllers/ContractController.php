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
            $search = strtolower($request->search);
            $searchPattern = '%'.$search.'%';
            
            $query->where(function ($q) use ($searchPattern) {
                $q->whereRaw("LPAD(id, 4, '0') LIKE ?", [$searchPattern])
                  ->orWhereHas('room', function($r) use ($searchPattern) {
                      $r->whereRaw('LOWER(room_number) LIKE ?', [$searchPattern]);
                  });
            });
        }

        $contracts = $query->paginate(10)->withQueryString();
        return view('contracts.index', compact('contracts'));
    }

    public function create()
    {
        // Get vacant rooms
        $vacantRooms = Room::where('status', 'vacant')->orderBy('room_number')->get();
        
        // Get rooms with available beds (occupied but not full)
        $roomsWithSpace = Room::where('status', 'occupied')
            ->whereHas('activeContract')
            ->with('activeContract.activeTenants')
            ->get()
            ->filter(function($room) {
                $currentTenants = $room->activeContract->activeTenants->count();
                return $currentTenants < $room->capacity;
            })
            ->sortBy('room_number');
        
        // Merge both collections
        $rooms = $vacantRooms->concat($roomsWithSpace)->sortBy('room_number');
        
        $tenants = Tenant::with('user')
            ->whereDoesntHave('contracts', fn ($q) => $q->where('is_active', true)->whereNull('left_on'))
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

        $room = Room::with('activeContract.activeTenants')->findOrFail($data['room_id']);
        $existingContract = $room->activeContract;

        // Check if room already has an active contract (adding tenants to existing contract)
        if ($existingContract) {
            return $this->addTenantsToExistingContract($request, $existingContract, $data);
        }

        // Otherwise, create a new contract
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

    /**
     * Add tenants to an existing contract (when room has available beds)
     */
    private function addTenantsToExistingContract(Request $request, Contract $contract, array $data)
    {
        $room = $contract->room;
        $currentTenantCount = $contract->activeTenants->count();
        $newTenantCount = count($data['tenant_ids']);
        $totalTenants = $currentTenantCount + $newTenantCount;

        // Check capacity
        if ($totalTenants > (int) $room->capacity) {
            return back()->withErrors([
                'tenant_ids' => "Room {$room->room_number} has {$currentTenantCount} tenant(s) and capacity for {$room->capacity}. You're trying to add {$newTenantCount} more, which exceeds the limit.",
            ])->withInput();
        }

        DB::beginTransaction();
        try {
            // Attach new tenants to existing contract
            $contract->tenants()->attach(
                collect($data['tenant_ids'])->mapWithKeys(fn ($tid) => [
                    $tid => ['joined_on' => $data['start_date'], 'left_on' => null],
                ])->toArray()
            );

            // Get billing rules
            $billingRules = \App\Models\Setting::billingRules();
            $depositMonths = $billingRules['deposit_months'];
            
            $monthlyRent = $room->monthly_rate;
            $totalDepositRequired = $monthlyRent * $depositMonths;
            $depositPerTenant = round($totalDepositRequired / $newTenantCount, 2);

            // Create TenantDeposit records for new tenants
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

            // Update all unpaid invoices to reflect new tenant count and redistribute shares
            $unpaidInvoices = Invoice::where('contract_id', $contract->id)
                ->whereNotIn('status', ['paid', 'void'])
                ->get();

            foreach ($unpaidInvoices as $invoice) {
                $rentPerTenant = round($invoice->rent_amount / $totalTenants, 2);
                $elecPerTenant = round($invoice->electricity_amount / $totalTenants, 2);
                $newShareAmount = $rentPerTenant + $elecPerTenant;

                // Update invoice tenant count
                $invoice->update(['tenant_count' => $totalTenants]);

                // Update existing tenant payment shares
                TenantPayment::where('invoice_id', $invoice->id)
                    ->whereNotIn('status', ['void'])
                    ->update(['share_amount' => $newShareAmount]);

                // Create tenant payment records for new tenants
                foreach ($data['tenant_ids'] as $tenantId) {
                    TenantPayment::create([
                        'invoice_id'          => $invoice->id,
                        'tenant_id'           => $tenantId,
                        'share_amount'        => $newShareAmount + $depositPerTenant,
                        'carry_over_balance'  => 0,
                        'amount_paid'         => 0,
                        'status'              => 'pending',
                        'method'              => null,
                        'paid_at'             => null,
                    ]);
                }
            }

            // Get tenant names for message
            $tenantNames = Tenant::whereIn('id', $data['tenant_ids'])
                ->with('user')
                ->get()
                ->pluck('user.name')
                ->join(', ');

            AuditLog::record('Added Tenants', 'Contract', $contract->id,
                "Added {$newTenantCount} tenant(s) ({$tenantNames}) to existing contract CT-" . str_pad($contract->id, 4, '0', STR_PAD_LEFT) . " for Room {$room->room_number}. Shares redistributed for unpaid invoices.");

            DB::commit();

            $contractId = 'CT-' . str_pad($contract->id, 4, '0', STR_PAD_LEFT);
            $message = "{$tenantNames} " . ($newTenantCount === 1 ? 'has' : 'have') . " been added to existing {$contractId} in Room {$room->room_number}. "
                     . "The new " . ($newTenantCount === 1 ? 'tenant' : 'tenants') . " will contribute to the monthly rent and deposit. "
                     . "All unpaid bills have been recalculated to include " . ($newTenantCount === 1 ? 'them' : 'all ' . $totalTenants . ' tenants') . ".";

            return redirect()->route('contracts.show', $contract)->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to add tenants to contract: ' . $e->getMessage()])->withInput();
        }
    }

    public function show(Contract $contract)
    {
        $contract->load([
            'room',
            'activeTenants.user',
            'invoices.tenantPayments.tenant.user', 
            'createdBy', 
            'depositEntries.recordedBy'
        ]);

        $tenantCount   = $contract->activeTenants->count();
        $rentPerTenant = $tenantCount > 0
            ? round($contract->room->monthly_rate / $tenantCount, 2)
            : $contract->room->monthly_rate;

        $depositBalance = $contract->depositBalance();

        return view('contracts.show', compact('contract', 'rentPerTenant', 'depositBalance'));
    }

    public function edit(Contract $contract)
    {
        abort_if(!$contract->is_active, 422, 'Cannot edit inactive contract.');
        
        // Load only active tenants (those who haven't left)
        $contract->load(['tenants' => function($query) {
            $query->wherePivot('left_on', null)->with('user');
        }, 'room']);
        
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

    /**
     * Remove a tenant from a contract.
     * This will:
     * - Detach the tenant from the contract
     * - Void unpaid invoices for that tenant
     * - Update room occupancy count
     * - Recalculate future billing statements and redistribute shares
     */
    public function removeTenant(Request $request, Contract $contract)
    {
        $data = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
        ]);

        $tenant = Tenant::findOrFail($data['tenant_id']);
        
        // Check if tenant is part of this contract
        if (!$contract->tenants->contains($tenant->id)) {
            return back()->withErrors(['error' => 'Tenant is not part of this contract.']);
        }

        // Check if this is the last tenant
        $currentActiveTenants = $contract->tenants()
            ->wherePivot('left_on', null)
            ->count();
            
        if ($currentActiveTenants <= 1) {
            return back()->withErrors(['error' => 'Cannot remove the last tenant. Please deactivate the contract instead.']);
        }

        // Check for unpaid tenant payments
        $unpaidPayments = TenantPayment::whereHas('invoice', function($query) use ($contract) {
                $query->where('contract_id', $contract->id);
            })
            ->where('tenant_id', $tenant->id)
            ->where('status', '!=', 'paid')
            ->where('status', '!=', 'void')
            ->with('invoice')
            ->get();

        if ($unpaidPayments->isNotEmpty()) {
            $invoiceIds = $unpaidPayments->pluck('invoice.id')->unique()->toArray();
            $invoiceNumbers = $unpaidPayments->pluck('invoice.invoice_number')->unique()->toArray();
            
            return response()->json([
                'error' => true,
                'message' => 'Cannot remove tenant with unpaid billing statements',
                'unpaid_invoices' => $invoiceIds,
                'invoice_numbers' => $invoiceNumbers,
            ], 422);
        }

        DB::transaction(function () use ($contract, $tenant) {
            // Mark tenant as left
            $contract->tenants()->updateExistingPivot($tenant->id, [
                'left_on' => now(),
            ]);

            // Get remaining active tenant IDs (after marking this one as left)
            $remainingTenantIds = $contract->tenants()
                ->wherePivot('left_on', null)
                ->pluck('tenants.id')
                ->toArray();
            
            $newTenantCount = count($remainingTenantIds);

            // Find all unpaid invoices for this contract
            $unpaidInvoices = Invoice::where('contract_id', $contract->id)
                ->whereNotIn('status', ['paid', 'void'])
                ->get();

            foreach ($unpaidInvoices as $invoice) {
                // Void the removed tenant's payment record
                TenantPayment::where('invoice_id', $invoice->id)
                    ->where('tenant_id', $tenant->id)
                    ->whereNotIn('status', ['paid'])
                    ->update(['status' => 'void']);

                // Recalculate share amounts for remaining tenants
                if ($newTenantCount > 0) {
                    $rentPerTenant = round($invoice->rent_amount / $newTenantCount, 2);
                    $elecPerTenant = round($invoice->electricity_amount / $newTenantCount, 2);
                    $newShareAmount = $rentPerTenant + $elecPerTenant;
                    
                    // Update invoice tenant count
                    $invoice->update([
                        'tenant_count' => $newTenantCount,
                    ]);
                    
                    // Update share amounts for all remaining active tenants
                    foreach ($remainingTenantIds as $remainingTenantId) {
                        TenantPayment::where('invoice_id', $invoice->id)
                            ->where('tenant_id', $remainingTenantId)
                            ->whereNotIn('status', ['void'])
                            ->update(['share_amount' => $newShareAmount]);
                    }
                }
            }

            // Update room occupancy status
            $room = $contract->room;
            $capacity = (int) $room->capacity;
            
            if ($newTenantCount === 0) {
                $room->update(['status' => 'vacant']);
            } elseif ($newTenantCount < $capacity) {
                $room->update(['status' => 'occupied']);
            }

            AuditLog::record('Removed Tenant', 'Contract', $contract->id,
                "Removed {$tenant->user->name} from contract. {$newTenantCount} tenant(s) remaining. Shares recalculated for unpaid invoices.");
        });

        // Reload contract to get fresh data
        $contract->refresh();
        
        $remainingTenants = $contract->tenants()
            ->wherePivot('left_on', null)
            ->count();
        $room = $contract->room;
        $capacity = (int) $room->capacity;
        $bedsAvailable = $capacity - $remainingTenants;
        
        $occupancyText = "{$remainingTenants}/{$capacity} occupied";
        $availabilityText = $bedsAvailable > 0 
            ? ($bedsAvailable === 1 
                ? "1 bed is available" 
                : "{$bedsAvailable} beds are available")
            : "room is full";

        $message = "{$tenant->user->name} has been removed from Contract CT-" . str_pad($contract->id, 4, '0', STR_PAD_LEFT) 
                 . ". The tenant's portal will show no active contract, and their unpaid bills have been redistributed to the remaining tenants. "
                 . "Room {$room->room_number}: {$availabilityText} ({$occupancyText}).";

        return redirect()->route('contracts.edit', $contract)->with('success', $message);
    }
}
