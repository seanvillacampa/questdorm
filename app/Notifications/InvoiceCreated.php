<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Models\TenantPayment;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to every tenant when their room's billing statement is auto-generated
 * (invoice_advance_days before the due date).
 *
 * Replaces InvoiceDueReminder. This email is the tenant's heads-up
 * that a new bill is ready and gives them the exact due date and
 * their share breakdown.
 */
class InvoiceCreated extends Notification
{
    public function __construct(
        public Invoice $invoice,
        public TenantPayment $tenantPayment,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $room      = $this->invoice->contract->room->room_number;
        $month     = now()->parse($this->invoice->billing_month)->format('F Y');
        $dueDate   = $this->invoice->due_date->format('M d, Y');
        $daysLeft  = (int) now()->diffInDays($this->invoice->due_date, false);
        $share     = number_format($this->tenantPayment->share_amount, 2);
        $rent      = number_format($this->invoice->rentPerTenant(), 2);
        $elec      = number_format($this->invoice->electricityPerTenant(), 2);
        $total     = number_format($this->invoice->total_amount, 2);
        $tenants   = $this->invoice->tenant_count;
        $name      = $notifiable->name ?? 'there';

        $subject = $daysLeft > 0
            ? "New Bill Ready — Room {$room} · Due in {$daysLeft} day(s)"
            : "New Bill Ready — Room {$room} · Due {$dueDate}";

        return (new MailMessage)
            ->subject($subject)
            ->greeting("Hello, {$name}!")
            ->line("Your bill for **Room {$room}** ({$month}) has been generated.")
            ->line("---")
            ->line("**Your share: ₱{$share}**")
            ->line("Rent share: ₱{$rent} (₱" . number_format($this->invoice->rent_amount, 2) . " ÷ {$tenants} tenant(s))")
            ->line("Electricity share: ₱{$elec}")
            ->line("---")
            ->line("**Due date: {$dueDate}**" . ($daysLeft > 0 ? " — {$daysLeft} day(s) from now" : ""))
            ->action('View My Bill & Pay Online', url('/login'))
            ->line('You can pay using **GCash, Maya, Card, or QR Ph** through PayMongo.')
            ->line('Please pay before the due date to avoid late charges.')
            ->salutation('— Quest Building');
    }
}
