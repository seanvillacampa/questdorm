<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantPayment extends Model
{
    protected $fillable = [
        'invoice_id',
        'tenant_id',
        'share_amount',
        'carry_over_balance',
        'amount_paid',
        'status',
        'method',
        'paid_at',
        'paymongo_link_id',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
        ];
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function balanceDue(): float
    {
        // Total owed = share + carry-over - amount paid
        $totalOwed = $this->share_amount + ($this->carry_over_balance ?? 0);
        return max(0, $totalOwed - $this->amount_paid);
    }
    
    /**
     * Total amount this tenant owes (including carry-over)
     */
    public function totalOwed(): float
    {
        return $this->share_amount + ($this->carry_over_balance ?? 0);
    }

    /**
     * Recompute this tenant payment's status based on:
     * 1. Payment status (paid or not)
     * 2. Invoice due date
     * 3. Cascading rule: if tenant has newer unpaid invoices that are overdue,
     *    all older unpaid invoices must be overdue
     *
     * Status rules:
     *   paid    — tenant paid their share
     *   pending — invoice created, due date not reached
     *   overdue — past due date (no grace period)
     */
    public function recomputeStatus(): void
    {
        // If already paid, don't change status
        if ($this->status === 'paid') {
            return;
        }

        $invoice = $this->invoice;
        
        // Calculate days past due (positive = future, negative = past)
        $daysPastDue = now()->startOfDay()->diffInDays($invoice->due_date->startOfDay(), false);

        // Determine base status from timing (no grace period - immediate overdue)
        $baseStatus = $daysPastDue > 0 ? 'pending' : 'overdue';

        // ── CASCADE RULE: Check if this tenant has newer unpaid invoices
        // If ANY newer invoice for this tenant is overdue, THIS one must be overdue too
        $newerUnpaid = TenantPayment::where('tenant_id', $this->tenant_id)
            ->whereHas('invoice', function($q) use ($invoice) {
                $q->where('contract_id', $invoice->contract_id)
                  ->where('billing_month', '>', $invoice->billing_month);
            })
            ->where('status', '!=', 'paid')
            ->exists();

        if ($newerUnpaid) {
            $baseStatus = 'overdue';
        }

        $this->update(['status' => $baseStatus]);
    }
}
