<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications\Ticket;

use App\Domain\Ticket\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class TicketStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Ticket $ticket,
        private readonly string $previousStatus,
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
            ->subject("Ticket {$this->ticket->ticketNumber} status updated")
            ->line("Your ticket {$this->ticket->ticketNumber}: \"{$this->ticket->subject}\" changed status.")
            ->line("From: {$this->previousStatus}")
            ->line("To: {$this->ticket->status}");
    }
}
