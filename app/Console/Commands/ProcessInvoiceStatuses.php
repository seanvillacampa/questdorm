<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\User;
use App\Notifications\PaymentMissed;
use App\Notifications\PaymentOverdue;
use App\Notifications\OwnerPaymentAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Runs daily at 00:05 AFTER invoices:generate-scheduled.
 *
 * Calls recomputeStatus() on every unpaid billing statement. That method
 * derives the correct status (pending/late/overdue) from the due
 * date and grace period settings, then sends notification emails
 * when status transitions occur.
 */
class ProcessInvoiceStatuses extends Command
{
    protected $signature   = 'invoices:process-statuses';
    protected $description = 'Recompute billing statement statuses (pending/late/overdue) and send reminder emails';

    public function handle(): void
    {
        $owner   = User::role('owner')->first();
        $updated = 0;

        $invoices = Invoice::with([
                'contract.room',
                'contract.tenants.user',
                'tenantPayments.tenant.user',
            ])
            ->whereNotIn('status', ['paid', 'void'])
            ->get();

        foreach ($invoices as $invoice) {
            $oldStatus = $invoice->status;

            // Recompute — this updates both the invoice and individual tenant payment rows
            $invoice->load('tenantPayments');
            $invoice->recomputeStatus();
            $invoice->refresh();

            $newStatus = $invoice->status;

            // Send emails on status transitions
            if ($oldStatus !== $newStatus) {
                $updated++;

                // Billing statement just became late — notify all unpaid tenants
                if ($newStatus === 'late') {
                    foreach ($invoice->tenantPayments->where('status', 'late') as $tp) {
                        $this->sendNotification(
                            $tp->tenant->user,
                            new PaymentMissed($invoice, $tp),
                            'PaymentMissed'
                        );
                    }
                }

                // Billing statement just became overdue — notify tenants + owner (once)
                if ($newStatus === 'overdue') {
                    foreach ($invoice->tenantPayments->where('status', 'overdue') as $tp) {
                        $this->sendNotification(
                            $tp->tenant->user,
                            new PaymentOverdue($invoice, $tp),
                            'PaymentOverdue'
                        );
                    }
                    if ($owner) {
                        $this->sendNotification($owner, new OwnerPaymentAlert($invoice), 'OwnerPaymentAlert');
                    }
                }
            }
        }

        $this->info("Status processing complete — {$updated} billing statement(s) transitioned.");
    }

    private function sendNotification($notifiable, $notification, string $label): void
    {
        try {
            $notifiable->notify($notification);
            Log::info("Notification sent: {$label}", ['to' => $notifiable->email ?? 'unknown']);
        } catch (\Exception $e) {
            Log::warning("Notification failed: {$label}", [
                'to'    => $notifiable->email ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
