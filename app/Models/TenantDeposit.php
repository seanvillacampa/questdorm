<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantDeposit extends Model
{
    protected $fillable = [
        'contract_id',
        'tenant_id',
        'amount_required',
        'amount_paid',
        'amount_deducted',
        'amount_refunded',
        'deduction_reason',
        'notes',
    ];

    // ── Relationships ────────────────────────────────────────────────────

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    /**
     * Calculate the current balance for this tenant's deposit.
     * Balance = amount_paid - amount_deducted - amount_refunded
     */
    public function balance(): float
    {
        return round(
            $this->amount_paid - $this->amount_deducted - $this->amount_refunded,
            2
        );
    }

    /**
     * Record a deduction from this tenant's deposit.
     */
    public function deduct(float $amount, string $reason): void
    {
        $this->increment('amount_deducted', $amount);
        $this->update([
            'deduction_reason' => $this->deduction_reason 
                ? $this->deduction_reason . "\n" . $reason 
                : $reason
        ]);
    }

    /**
     * Record a refund of this tenant's deposit.
     */
    public function refund(float $amount): void
    {
        $this->increment('amount_refunded', $amount);
    }
}
