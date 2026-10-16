<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\TenantPayment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateCarryOvers extends Command
{
    protected $signature = 'invoices:recalculate-carryovers';
    protected $description = 'Recalculate carry-over balances for all existing invoices';

    public function handle()
    {
        $this->info('Recalculating carry-over balances for all invoices...');
        
        $contracts = Contract::with(['invoices' => function($q) {
            $q->orderBy('billing_month');
        }])->get();
        
        $updated = 0;
        
        foreach ($contracts as $contract) {
            $this->line("Processing Contract #{$contract->id}...");
            
            foreach ($contract->invoices as $invoice) {
                if ($invoice->status === 'void') {
                    continue;
                }
                
                $month = $invoice->billing_month;
                $totalRoomCarryOver = 0.0;
                $totalRoomCredit = 0.0;
                
                // Recalculate for each tenant in this invoice
                $tenantPayments = TenantPayment::where('invoice_id', $invoice->id)->get();
                
                foreach ($tenantPayments as $tp) {
                    // Get ALL previous invoices for this tenant
                    $allPreviousPayments = TenantPayment::whereHas('invoice', function($q) use ($contract, $month) {
                        $q->where('contract_id', $contract->id)
                          ->where('billing_month', '<', $month)
                          ->whereNotIn('status', ['void']);
                    })
                    ->where('tenant_id', $tp->tenant_id)
                    ->orderBy('invoice_id')
                    ->get();
                    
                    $accumulatedBalance = 0.0;

                    foreach ($allPreviousPayments as $prevPayment) {
                        // Use ONLY share_amount - amount_paid (base debt for that month).
                        // DO NOT call balanceDue() here — that method includes the payment's
                        // own carry_over_balance which would double-count carry-overs
                        // that were already rolled into a later month's carry_over_balance.
                        $baseDebt = $prevPayment->share_amount - $prevPayment->amount_paid;
                        if ($baseDebt > 0) {
                            $accumulatedBalance += $baseDebt;
                        }
                    }

                    $newCarryOver = round(max(0, $accumulatedBalance), 2);
                    
                    // Update tenant payment
                    if ($tp->carry_over_balance != $newCarryOver) {
                        $old = $tp->carry_over_balance;
                        $tp->update(['carry_over_balance' => $newCarryOver]);
                        $this->line("  Tenant #{$tp->tenant_id} {$invoice->invoice_number}: ₱{$old} → ₱{$newCarryOver}");
                        $updated++;
                    }
                    
                    $totalRoomCarryOver += $newCarryOver;
                    
                    if ($netBalance < 0) {
                        $totalRoomCredit += round(abs($netBalance), 2);
                    }
                }
                
                // Update invoice totals
                $invoice->update([
                    'carry_over_balance' => $totalRoomCarryOver,
                    'credit_balance' => $totalRoomCredit,
                    'total_amount' => max(0, round(
                        $invoice->rent_amount + 
                        $invoice->electricity_amount + 
                        $totalRoomCarryOver - 
                        $totalRoomCredit, 
                        2
                    )),
                ]);
            }
        }
        
        $this->info("✓ Updated {$updated} tenant payment carry-over balances");
        
        return 0;
    }
}
