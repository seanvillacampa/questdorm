<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\TenantDeposit;
use App\Models\TenantPayment;
use Illuminate\Console\Command;

class FixDepositRecords extends Command
{
    protected $signature = 'deposits:fix';
    protected $description = 'Fix tenant deposit records for paid first invoices';

    public function handle()
    {
        $this->info('Fixing deposit records for paid first invoices...');
        
        $contracts = Contract::with(['invoices' => function($q) {
            $q->orderBy('billing_month');
        }])->get();
        
        $fixed = 0;
        
        foreach ($contracts as $contract) {
            $firstInvoice = $contract->invoices->first();
            
            if (!$firstInvoice) {
                continue;
            }
            
            // Get all paid tenant payments for this first invoice
            $tenantPayments = TenantPayment::where('invoice_id', $firstInvoice->id)
                ->where('status', 'paid')
                ->get();
            
            foreach ($tenantPayments as $tp) {
                // Find the tenant deposit record
                $tenantDeposit = TenantDeposit::where('contract_id', $contract->id)
                    ->where('tenant_id', $tp->tenant_id)
                    ->first();
                
                if ($tenantDeposit && $tenantDeposit->amount_paid < $tenantDeposit->amount_required) {
                    $this->line("Contract #{$contract->id} - Tenant #{$tp->tenant_id}: Updating deposit from ₱{$tenantDeposit->amount_paid} to ₱{$tenantDeposit->amount_required}");
                    
                    $tenantDeposit->update([
                        'amount_paid' => $tenantDeposit->amount_required,
                    ]);
                    
                    $fixed++;
                }
            }
        }
        
        $this->info("Fixed {$fixed} tenant deposit records");
        
        return 0;
    }
}
