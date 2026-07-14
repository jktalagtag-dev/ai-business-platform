<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Contracts\Repositories\Ticket\TicketAttachmentRepositoryInterface;
use App\Application\Contracts\Repositories\Ticket\TicketCommentRepositoryInterface;
use App\Application\Contracts\Repositories\Ticket\TicketRepositoryInterface;
use App\Application\Contracts\Services\TicketCodeGeneratorInterface;
use App\Application\Events\Ticket\TicketAssigned;
use App\Application\Events\Ticket\TicketCreated;
use App\Application\Events\Ticket\TicketStatusChanged;
use App\Application\Listeners\Ticket\DispatchCriticalTicketEscalation;
use App\Application\Listeners\Ticket\DispatchStatusChangeNotification;
use App\Application\Listeners\Ticket\DispatchTicketAssignmentNotification;
use App\Domain\Ticket\Ticket;
use App\Infrastructure\Persistence\Eloquent\Repositories\Ticket\TicketAttachmentRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\Ticket\TicketCommentRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\Ticket\TicketRepository;
use App\Infrastructure\Ticket\TicketCodeGenerator;
use App\Policies\Ticket\TicketPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class TicketServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TicketRepositoryInterface::class, TicketRepository::class);
        $this->app->bind(TicketCommentRepositoryInterface::class, TicketCommentRepository::class);
        $this->app->bind(TicketAttachmentRepositoryInterface::class, TicketAttachmentRepository::class);
        $this->app->bind(TicketCodeGeneratorInterface::class, TicketCodeGenerator::class);
    }

    public function boot(): void
    {
        Gate::policy(Ticket::class, TicketPolicy::class);

        Event::listen(TicketCreated::class, DispatchCriticalTicketEscalation::class);
        Event::listen(TicketAssigned::class, DispatchTicketAssignmentNotification::class);
        Event::listen(TicketStatusChanged::class, DispatchStatusChangeNotification::class);
    }
}
