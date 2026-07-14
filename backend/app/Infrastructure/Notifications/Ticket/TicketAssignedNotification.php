<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications\Ticket;

use App\Domain\Ticket\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class TicketAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Ticket $ticket) {}

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
            ->subject("Ticket {$this->ticket->ticketNumber} assigned to you")
            ->line("You have been assigned ticket {$this->ticket->ticketNumber}: \"{$this->ticket->subject}\".")
            ->line("Priority: {$this->ticket->priority}")
            ->line("Type: {$this->ticket->type}");
    }
}
