<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One billing statement per room per billing month.
 *
 * Status is derived from tenantPayments:
 *   paid     — ALL tenants paid
 *   partial  — SOME tenants paid, others haven't (regardless of their status)
 *   late     — NO one paid, billing statement is within the grace period
 *   overdue  — NO one paid, grace period has ended
 *   pending  — NO one paid, due date not yet reached (billing statement just created)
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
            
            // ── CASCADE PAYMENT: If this billing statement included carry_over_balance
            // and is now fully paid, mark all older unpaid billing statements as paid
            if (($this->carry_over_balance ?? 0) > 0) {
                $this->cascadePaymentToOlderInvoices();
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
     * Cascade payment to all older unpaid billing statements.
     * 
     * This is called when a billing statement with carry_over_balance is fully paid,
     * meaning tenants have paid for their current month PLUS all past unpaid amounts.
     * 
     * For each tenant who paid their share in this billing statement, mark all their
     * older unpaid billing statements as paid.
     */
    private function cascadePaymentToOlderInvoices(): void
    {
        $tenantsPaid = $this->tenantPayments->where('status', 'paid');
        
        foreach ($tenantsPaid as $tenantPay) {
            // Find all older unpaid billing statements for this tenant
            $olderUnpaidInvoices = TenantPayment::where('tenant_id', $tenantPay->tenant_id)
                ->whereHas('invoice', function($q) {
                    $q->where('contract_id', $this->contract_id)
                      ->where('billing_month', '<', $this->billing_month);
                })
                ->where('status', '!=', 'paid')
                ->with('invoice')
                ->orderBy('invoice_id')
                ->get();
            
            if ($olderUnpaidInvoices->isEmpty()) continue;
            
            foreach ($olderUnpaidInvoices as $oldTenantPay) {
                // Mark as paid
                $oldTenantPay->update([
                    'status' => 'paid',
                    'amount_paid' => $oldTenantPay->share_amount,
                    'paid_at' => now(),
                ]);
                
                \Log::info('Cascaded payment to older billing statement', [
                    'tenant_id' => $tenantPay->tenant_id,
                    'old_invoice' => $oldTenantPay->invoice->invoice_number,
                    'billing_month' => $oldTenantPay->invoice->billing_month,
                    'triggered_by' => $this->invoice_number,
                ]);
                
                // Recompute the old billing statement's status
                $oldTenantPay->invoice->refresh();
                $oldTenantPay->invoice->recomputeStatusWithoutCascade();
            }
        }
    }

    /**
     * Recompute status without triggering cascade logic.
     * Used when updating old billing statements to avoid infinite loops.
     */
    private function recomputeStatusWithoutCascade(): void
    {
        $tp = $this->tenantPayments()->get();
        if ($tp->isEmpty()) return;

        $total = $tp->count();
        $paid = $tp->where('status', 'paid')->count();

        if ($paid === $total) {
            $status = 'paid';
        } elseif ($paid > 0) {
            $status = 'partial';
        } else {
            $hasOverdue = $tp->contains('status', 'overdue');
            $status = $hasOverdue ? 'overdue' : 'pending';
        }

        $amountPaid = $tp->sum('amount_paid');
        $paidAt = $paid === $total ? ($tp->max('paid_at') ?? now()) : null;

        $this->update([
            'status' => $status,
            'amount_paid' => $amountPaid,
            'paid_at' => $paidAt,
        ]);
    }
}
