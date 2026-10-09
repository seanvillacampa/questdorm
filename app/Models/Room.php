<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'room_number',
        'floor',
        'capacity',
        'monthly_rate',
        'deposit_required',
        'meter_number',
        'status',
        'is_airconditioned',
    ];

    // ── Relationships ────────────────────────────────────────────────────

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    /** The single active contract for this room, if any. */
    public function activeContract()
    {
        return $this->hasOne(Contract::class)->where('status', 'active')->latestOfMany();
    }

    // ── Helpers used by the dashboard ───────────────────────────────────

    /**
     * Append currentInvoice (the latest invoice for this room's active
     * contract in the given billing month) and currentTenantName onto
     * a collection of rooms in one extra query each — avoids N+1.
     *
     * Called from DashboardController; results are set as dynamic
     * properties on each Room instance so the view can just read them.
     */
    public static function withDashboardData(string $billingMonth, $rooms): void
    {
        // Gather active contract ids for these rooms
        $contractIds = $rooms
            ->pluck('activeContract')
            ->filter()
            ->pluck('id')
            ->unique();

        // Fetch all relevant invoices for this month in one query
        $invoices = Invoice::whereIn('contract_id', $contractIds)
            ->where('billing_month', $billingMonth)
            ->get()
            ->keyBy('contract_id');

        // Fetch tenant names for active contracts
        $tenantNames = \DB::table('contract_tenants')
            ->join('tenants', 'tenants.id', '=', 'contract_tenants.tenant_id')
            ->join('users', 'users.id', '=', 'tenants.user_id')
            ->whereIn('contract_tenants.contract_id', $contractIds)
            ->whereNull('contract_tenants.left_on')
            ->select('contract_tenants.contract_id', \DB::raw("GROUP_CONCAT(users.name ORDER BY users.name SEPARATOR ', ') as names"))
            ->groupBy('contract_tenants.contract_id')
            ->pluck('names', 'contract_id');

        foreach ($rooms as $room) {
            $contractId = $room->activeContract?->id;
            $room->currentInvoice    = $contractId ? $invoices->get($contractId) : null;
            $room->currentTenantName = $contractId ? $tenantNames->get($contractId) : null;
        }
    }
}
