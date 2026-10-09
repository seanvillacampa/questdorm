<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent when staff creates a new tenant account.
 * Sends synchronously (no queue) so the email goes out immediately
 * when the tenant is registered.
 */
class TenantWelcome extends Notification
{
    public string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('Welcome to Quest Building — Set Your Password')
            ->greeting('Hello, ' . ($notifiable->name ?? 'there') . '!')
            ->line('Your account has been created by the management office.')
            ->line('Click the button below to set your password and activate your account.')
            ->action('Set My Password', $url)
            ->line('This link expires in ' . config('auth.passwords.users.expire', 60) . ' minutes.')
            ->line('**After setting your password, you can access your tenant portal at:** https://questdorm.onrender.com')
            ->line('If you did not expect this email, you can safely ignore it.')
            ->salutation('— Quest Building');
    }
}
