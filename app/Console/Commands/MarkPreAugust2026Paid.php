<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\TenantPayment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MarkPreAugust2026Paid extends Command
{
    protected $signature = 'invoices:mark-pre-august-paid';
    protected $description = 'Mark all invoices before August 2026 as fully paid';

    public function handle()
    {
        $this->info('Marking all invoices before August 2026 as paid...');
        
        DB::beginTransaction();
        
        try {
            // Get all invoices before August 2026 that aren't already paid
            $invoices = Invoice::where('billing_month', '<', '2026-08')
                ->whereNotIn('status', ['paid', 'void'])
                ->with('tenantPayments')
                ->get();
            
            $invoiceCount = 0;
            $tenantPaymentCount = 0;
            
            foreach ($invoices as $invoice) {
                $this->line("Processing {$invoice->invoice_number} ({$invoice->billing_month})...");
                
                // Mark each tenant payment as fully paid
                foreach ($invoice->tenantPayments as $tp) {
                    if ($tp->status !== 'paid') {
                        $totalOwed = $tp->totalOwed();
                        
                        $tp->update([
                            'amount_paid' => $totalOwed,
                            'status' => 'paid',
                            'paid_at' => $invoice->due_date,
                            'method' => 'cash',
                        ]);
                        
                        $tenantPaymentCount++;
                        $this->line("  ✓ Tenant #{$tp->tenant_id}: ₱{$totalOwed} paid");
                    }
                }
                
                // Update invoice to paid status
                $invoice->update([
                    'amount_paid' => $invoice->total_amount,
                    'status' => 'paid',
                    'paid_at' => $invoice->due_date,
                ]);
                
                $invoiceCount++;
            }
            
            DB::commit();
            
            $this->info("✓ Marked {$invoiceCount} invoices as paid");
            $this->info("✓ Updated {$tenantPaymentCount} tenant payments");
            
            return 0;
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
}
