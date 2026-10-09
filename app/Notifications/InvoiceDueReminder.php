<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Models\TenantPayment;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent on the due date to every tenant sharing the room.
 * Each tenant receives their own email with their personal share amount.
 */
class InvoiceDueReminder extends Notification
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
        $room    = $this->invoice->contract->room->room_number;
        $month   = now()->parse($this->invoice->billing_month)->format('F Y');
        $amount  = number_format($this->tenantPayment->share_amount, 2);
        $dueDate = $this->invoice->due_date->format('M d, Y');

        return (new MailMessage)
            ->subject("Payment Due — Room {$room} · {$month}")
            ->greeting("Hello, {(\$notifiable->name ?? 'there')}!")
            ->line("Your bill for **Room {$room}** ({$month}) is due today, **{$dueDate}**.")
            ->line("**Your share: ₱{$amount}**")
            ->line("Room total: ₱" . number_format($this->invoice->total_amount, 2) .
                   " shared among {$this->invoice->tenant_count} tenant(s).")
            ->action('Pay with PayMongo', url('/login'))
            ->line('You can pay using GCash, Maya, Card, or QR Ph.')
            ->line('If you have already paid, please disregard this message.')
            ->salutation('— Quest Building');
    }
}
