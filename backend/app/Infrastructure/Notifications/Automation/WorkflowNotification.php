<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications\Automation;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Generic subject/body mail notification sent by the `send_notification`
 * automation action — unlike the per-domain Ticket notifications, the
 * content here is entirely workflow-author-supplied (already resolved of
 * {{placeholders}} by the time it reaches this class).
 */
final class WorkflowNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $subject,
        private readonly string $message,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject)
            ->line($this->message);
    }
}
