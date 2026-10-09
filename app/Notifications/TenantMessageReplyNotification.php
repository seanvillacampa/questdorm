<?php

namespace App\Notifications;

use App\Models\TenantMessage;
use App\Models\TenantMessageReply;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantMessageReplyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public TenantMessage $message,
        public TenantMessageReply $reply,
        public User $repliedBy
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $isStaffReply = $this->repliedBy->hasAnyRole(['owner', 'employee']);
        $subject = $isStaffReply 
            ? 'Management replied to your message' 
            : 'New reply to your concern';

        // Determine the URL based on who is receiving the notification
        if ($notifiable->hasRole('tenant')) {
            $url = route('tenant.messages.show', $this->message);
        } else {
            $url = route('tenant-messages.show', $this->message);
        }

        return (new MailMessage)
            ->subject($subject . ' - Quest Building')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line($this->repliedBy->name . ' replied to: **' . $this->message->subject . '**')
            ->line('**Reply:**')
            ->line($this->reply->message)
            ->action('View Conversation', $url)
            ->line('You can view the full conversation and reply through your portal.')
            ->salutation('Best regards, Quest Building Management');
    }
}
