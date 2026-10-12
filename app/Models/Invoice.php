<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One billing statement per room per billing month.
 *
 * Status is derived from tenantPayments:
 *   paid     — ALL tenants paid
 *   partial  — SOME tenants paid, others haven't
 *   overdue  — NO one paid, past grace period
 *   pending  — NO one paid, not yet generated or due date not reached
 *   void     — cancelled
 */
class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'contract_id',
        'tenant_count',
        'billing_month',
        'due_date',
        'rent_amount',
        'electricity_amount',
        'carry_over_balance',
        'credit_balance',
        'total_amount',
        'amount_paid',
        'status',
        'paid_at',
        'paymongo_link_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'paid_at'  => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function tenantPayments()
    {
        return $this->hasMany(TenantPayment::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    public function balanceDue(): float
    {
        return max(0, $this->total_amount - $this->amount_paid);
    }

    /** Rent share per tenant = room rent ÷ number of tenants. */
    public function rentPerTenant(): float
    {
        $count = max(1, $this->tenant_count);
        return round($this->rent_amount / $count, 2);
    }

    /** Electricity share per tenant = electricity ÷ number of tenants. */
    public function electricityPerTenant(): float
    {
        $count = max(1, $this->tenant_count);
        return round($this->electricity_amount / $count, 2);
    }

    /** Total amount each tenant owes (rent share + electricity share). */
    public function amountPerTenant(): float
    {
        return $this->rentPerTenant() + $this->electricityPerTenant();
    }

    /**
     * Effective total due for the room after carry-over and credit.
     *   effective_total = (rent + electricity + carry_over_balance) - credit_balance
     */
    public function effectiveTotal(): float
    {
        return max(0, $this->rent_amount + $this->electricity_amount
            + ($this->carry_over_balance ?? 0)
            - ($this->credit_balance ?? 0));
    }

    /**
     * Effective share per tenant = effectiveTotal ÷ tenant_count.
     */
    public function effectiveSharePerTenant(): float
    {
        return round($this->effectiveTotal() / max(1, $this->tenant_count), 2);
    }

    /**
     * Recompute the billing statement-level status from its tenant payment rows.
     *
     * Rules (in order of priority):
     *   paid    — every tenant paid
     *   partial — at least one paid, at least one not paid
     *   overdue — nobody paid AND at least one tenant payment is "overdue"
     *   pending — nobody paid AND all tenant payments are "pending"
     *
     * This method first recomputes each TenantPayment status individually,
     * then aggregates them to determine the billing statement-level status.
     * 
     * If billing statement becomes fully paid and has carry_over_balance, it will
     * automatically mark all older unpaid billing statements as paid.
     */
    public function recomputeStatus(): void
    {
        $tp = $this->tenantPayments;
        if ($tp->isEmpty()) return;

        // First, recompute each tenant payment's individual status
        // This handles the cascade logic at the tenant level
        foreach ($tp as $tenantPay) {
            $tenantPay->recomputeStatus();
        }

        // Refresh to get updated statuses
        $tp = $this->tenantPayments()->get();

        $total  = $tp->count();
        $paid   = $tp->where('status', 'paid')->count();

        // Determine invoice-level status based on tenant payment statuses
        if ($paid === $total) {
            $status = 'paid';

            // Cascade to older invoices for every tenant that paid
            if (($this->carry_over_balance ?? 0) > 0) {
                foreach ($tp->where('status', 'paid') as $tenantPay) {
                    $this->cascadePaymentToOlderInvoices($tenantPay->tenant_id);
                }
            }
        } elseif ($paid > 0) {
            $status = 'partial';
        } else {
            // Nobody paid — aggregate from tenant payment statuses
            $hasOverdue = $tp->contains('status', 'overdue');
            
            $status = $hasOverdue ? 'overdue' : 'pending';
        }

        $amountPaid = $tp->sum('amount_paid');
        $paidAt     = $paid === $total ? ($tp->max('paid_at') ?? now()) : null;

        $this->update([
            'status'      => $status,
            'amount_paid' => $amountPaid,
            'paid_at'     => $paidAt,
        ]);
    }

    /**
     * Cascade payment to all older unpaid billing statements for a given tenant.
     *
     * Called after a tenant is marked paid on THIS invoice (which already rolled up
     * all their previous unpaid balances via carry_over_balance). Every older
     * TenantPayment row for this tenant should now be stamped paid so the
     * historical invoices flip to "paid" as well.
     *
     * Public so PaymentController and PayMongoController can call it directly
     * after marking the tenant payment paid, without needing to go through
     * recomputeStatus() which would re-query.
     */
    public function cascadePaymentToOlderInvoices(int $tenantId, string $method = 'online'): void
    {
        $olderUnpaid = TenantPayment::where('tenant_id', $tenantId)
            ->whereHas('invoice', function ($q) {
                $q->where('contract_id', $this->contract_id)
                  ->where('billing_month', '<', $this->billing_month)
                  ->whereNotIn('status', ['void']);
            })
            ->whereIn('status', ['pending', 'overdue', 'partial', 'late', 'partial_late', 'partial_overdue'])
            ->with('invoice')
            ->orderBy('invoice_id')
            ->get();

        foreach ($olderUnpaid as $oldTp) {
            // Set amount_paid to share_amount only — the carry_over on this old row
            // was already included in the CURRENT invoice's carry_over_balance and
            // the tenant has now paid that. We just need to zero out the base debt.
            $oldTp->update([
                'status'      => 'paid',
                'amount_paid' => $oldTp->share_amount,
                'method'      => $method,
                'paid_at'     => now(),
            ]);

            \Log::info('Cascaded payment to older billing statement', [
                'tenant_id'    => $tenantId,
                'old_invoice'  => $oldTp->invoice->invoice_number,
                'triggered_by' => $this->invoice_number,
            ]);

            // Recompute the old invoice status without triggering another cascade
            $oldTp->invoice->refresh();
            $oldTp->invoice->recomputeStatusWithoutCascade();
        }
    }

    /**
     * Recompute status without triggering cascade logic.
     * Used when updating old billing statements to avoid infinite loops.
     */
    public function recomputeStatusWithoutCascade(): void
    {
        $tp = $this->tenantPayments()->get();
        if ($tp->isEmpty()) return;

        $total = $tp->count();
        $paid  = $tp->where('status', 'paid')->count();

        if ($paid === $total) {
            $status = 'paid';
        } elseif ($paid > 0) {
            $status = 'partial';
        } else {
            $hasOverdue = $tp->contains('status', 'overdue');
            $status     = $hasOverdue ? 'overdue' : 'pending';
        }

        $amountPaid = $tp->sum('amount_paid');
        $paidAt     = ($paid === $total) ? ($tp->max('paid_at') ?? now()) : null;

        $this->update([
            'status'      => $status,
            'amount_paid' => $amountPaid,
            'paid_at'     => $paidAt,
        ]);
    }
}
