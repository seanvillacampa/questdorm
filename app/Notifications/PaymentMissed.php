<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Models\TenantPayment;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to a tenant when their payment becomes LATE
 * (past due date, within grace period).
 */
class PaymentMissed extends Notification
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
        $daysLate = now()->diffInDays($this->invoice->due_date);

        return (new MailMessage)
            ->subject("⚠️ Payment Late — Room {$room} · {$month}")
            ->greeting("Hello, {(\$notifiable->name ?? 'there')}!")
            ->line("Your payment for **Room {$room}** ({$month}) is now **{$daysLate} day(s) late**.")
            ->line("**Your outstanding share: ₱{$amount}**")
            ->line('Please settle your payment as soon as possible to avoid further action.')
            ->action('Pay Now with PayMongo', url(route('tenant.bill')))
            ->line('Accepts GCash, Maya, Card, and QR Ph.')
            ->salutation('— Quest Building');
    }
}
