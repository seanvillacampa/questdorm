<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use Illuminate\Console\Command;

/**
 * One-time (and on-demand) command to resync all billing statement statuses
 * from their tenant payment rows. Run this after any bulk data
 * changes or after updating billing logic.
 */
class ResyncInvoiceStatuses extends Command
{
    protected $signature   = 'invoices:resync-statuses';
    protected $description = 'Resync all billing statement statuses from tenant payment rows';

    public function handle(): void
    {
        $invoices = Invoice::with('tenantPayments')->whereNotIn('status', ['void'])->get();
        $bar      = $this->output->createProgressBar($invoices->count());
        $bar->start();

        foreach ($invoices as $invoice) {
            $invoice->recomputeStatus();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Resynced {$invoices->count()} billing statement(s).");
    }
}
