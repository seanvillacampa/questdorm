<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\TenantPayment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RecalculateCarryOversOnce extends Command
{
    protected $signature = 'carryovers:fix-once';
    protected $description = 'One-time fix: Recalculate all carry-over balances (runs automatically on first boot)';

    public function handle(): int
    {
        // Check if already run
        $cacheKey = 'carryovers_recalculated_v1';
        if (Cache::has($cacheKey)) {
            $this->info('Carry-overs already recalculated. Skipping.');
            return 0;
        }

        $this->info('Recalculating carry-over balances...');
        
        $contracts = Contract::with(['invoices' => function($q) {
            $q->orderBy('billing_month');
        }])->get();
        
        $updated = 0;
        
        foreach ($contracts as $contract) {
            foreach ($contract->invoices as $invoice) {
                if ($invoice->status === 'void') {
                    continue;
                }
                
                $month = $invoice->billing_month;
                $totalRoomCarryOver = 0.0;
                
                $tenantPayments = TenantPayment::where('invoice_id', $invoice->id)->get();
                
                foreach ($tenantPayments as $tp) {
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
                        $balance = $prevPayment->balanceDue();
                        if ($balance > 0) {
                            $accumulatedBalance += $balance;
                        }
                    }
                    
                    $newCarryOver = round($accumulatedBalance, 2);
                    
                    if ($tp->carry_over_balance != $newCarryOver) {
                        $tp->update(['carry_over_balance' => $newCarryOver]);
                        $updated++;
                    }
                    
                    $totalRoomCarryOver += $newCarryOver;
                }
                
                $invoice->update([
                    'carry_over_balance' => $totalRoomCarryOver,
                    'total_amount' => max(0, round(
                        $invoice->rent_amount + 
                        $invoice->electricity_amount + 
                        $totalRoomCarryOver - 
                        ($invoice->credit_balance ?? 0), 
                        2
                    )),
                ]);
            }
        }
        
        // Mark as completed
        Cache::forever($cacheKey, true);
        
        Log::info("Carry-overs recalculated: {$updated} tenant payments updated");
        $this->info("✓ Updated {$updated} tenant payment carry-over balances");
        
        return 0;
    }
}
