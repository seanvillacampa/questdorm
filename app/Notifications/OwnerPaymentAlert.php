<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent ONCE to the owner when a billing statement transitions to OVERDUE
 * (grace period has ended with no payment). This is the signal
 * to personally follow up with the tenant(s).
 */
class OwnerPaymentAlert extends Notification
{
    public function __construct(public Invoice $invoice) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $room      = $this->invoice->contract->room->room_number;
        $month     = now()->parse($this->invoice->billing_month)->format('F Y');
        $balance   = number_format($this->invoice->balanceDue(), 2);
        $dueDate   = $this->invoice->due_date->format('M d, Y');
        $daysLate  = (int) now()->diffInDays($this->invoice->due_date);
        $name      = $notifiable->name ?? 'there';

        $tenants = $this->invoice->contract->tenants
            ->map(fn ($t) => $t->user->name)
            ->implode(', ');

        // List which specific tenants are still unpaid
        $unpaidTenants = $this->invoice->tenantPayments
            ->where('status', 'overdue')
            ->map(fn ($tp) => $tp->tenant->user->name . ' (owes ₱' . number_format($tp->balanceDue(), 2) . ')')
            ->implode(', ');

        return (new MailMessage)
            ->subject("🚨 Overdue Payment — Room {$room} · {$month}")
            ->greeting("Hello, {$name}!")
            ->line("**Room {$room}** has an overdue bill for **{$month}**.")
            ->line("The billing statement was due on **{$dueDate}** and the grace period has now ended ({$daysLate} day(s) late).")
            ->line("---")
            ->line("**Balance remaining: ₱{$balance}**")
            ->line("**Tenant(s):** {$tenants}")
            ->line("**Unpaid shares:** {$unpaidTenants}")
            ->line("---")
            ->line("This is a one-time alert. Please follow up directly with the tenant(s) listed above.")
            ->action('View Billing Statement', url(route('invoices.index')))
            ->salutation('— Quest Building (automated alert)');
    }
}
