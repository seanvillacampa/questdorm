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
                    $totalCredit = 0.0;
                    
                    foreach ($allPreviousPayments as $prevPayment) {
                        $balance = $prevPayment->balanceDue();
                        
                        if ($balance > 0) {
                            $accumulatedBalance += $balance;
                        } else {
                            // Check for overpayment credit
                            $tenantTotal = $prevPayment->totalOwed();
                            if ($prevPayment->amount_paid > $tenantTotal) {
                                $totalCredit += ($prevPayment->amount_paid - $tenantTotal);
                            }
                        }
                    }
                    
                    // Calculate net carry-over
                    $netBalance = $accumulatedBalance - $totalCredit;
                    $newCarryOver = max(0, round($netBalance, 2));
                    
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
