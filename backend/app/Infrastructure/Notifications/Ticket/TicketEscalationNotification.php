<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications\Ticket;

use App\Domain\Ticket\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class TicketEscalationNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Ticket $ticket,
        private readonly string $reason,
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
            ->subject("Ticket {$this->ticket->ticketNumber} needs attention")
            ->line("Ticket {$this->ticket->ticketNumber}: \"{$this->ticket->subject}\" has been escalated.")
            ->line("Reason: {$this->reason}")
            ->line("Priority: {$this->ticket->priority}");
    }
}
