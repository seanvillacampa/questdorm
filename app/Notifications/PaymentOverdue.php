<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Models\TenantPayment;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to a tenant when their payment becomes OVERDUE
 * (grace period has ended — owner is also alerted separately).
 */
class PaymentOverdue extends Notification
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

        return (new MailMessage)
            ->subject("🚨 Payment Overdue — Room {$room} · {$month}")
            ->greeting("Hello, {(\$notifiable->name ?? 'there')}!")
            ->line("Your payment for **Room {$room}** ({$month}) is now **OVERDUE**.")
            ->line("**Amount owed: ₱{$amount}**")
            ->line('The grace period has ended. Please contact the management office immediately or settle online.')
            ->action('Pay Now with PayMongo', url('/login'))
            ->line('Failure to pay may result in penalties or contract review.')
            ->salutation('— Quest Building');
    }
}
