<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Models\TenantPayment;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to a tenant when their payment is confirmed (by PayMongo or by staff).
 */
class PaymentReceived extends Notification
{
    public function __construct(
        public Invoice $invoice,
        public TenantPayment $tenantPayment,
        public float $amountPaid,
        public string $method = 'cash',
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $room    = $this->invoice->contract->room->room_number;
        $month   = now()->parse($this->invoice->billing_month)->format('F Y');
        $paid    = number_format($this->amountPaid, 2);
        $balance = number_format($this->tenantPayment->balanceDue(), 2);
        $method  = ucfirst(str_replace('_', ' ', $this->method));

        return (new MailMessage)
            ->subject("✅ Payment Received — Room {$room} · {$month}")
            ->greeting("Hello, {(\$notifiable->name ?? 'there')}!")
            ->line("We have received your payment for **Room {$room}** ({$month}).")
            ->line("**Amount received: ₱{$paid}** via {$method}")
            ->line($this->tenantPayment->balanceDue() > 0
                ? "**Remaining balance: ₱{$balance}** — please settle the rest before the grace period ends."
                : "**Your share is fully paid. Thank you!**")
            ->action('View Your Bill', url(route('tenant.bill')))
            ->salutation('— Quest Building');
    }
}
