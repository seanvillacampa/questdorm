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

        $contract = Contract::create([
            'room_id'           => $data['room_id'],
            'start_date'        => $data['start_date'],
            'end_date'          => null, // no end date — deactivated manually
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

        // Create TenantDeposit records for each tenant
        $tenantCount = count($data['tenant_ids']);
        $depositPerTenant = $tenantCount > 0 ? round($room->deposit_required / $tenantCount, 2) : 0;

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

        Room::where('id', $data['room_id'])->update(['status' => 'occupied']);

        AuditLog::record('Created', 'Contract', $contract->id,
            "Contract for Room {$room->room_number} — " . count($data['tenant_ids']) . " tenant(s) @ ₱" . number_format($room->monthly_rate, 2) . "/month room rate, ₱" . number_format($depositPerTenant, 2) . " deposit/tenant");

        return redirect()->route('contracts.index')
            ->with('success', 'Contract created. Rent is ₱' . number_format($room->monthly_rate / $tenantCount, 2) . '/tenant. Deposit is ₱' . number_format($depositPerTenant, 2) . '/tenant.');
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
