<?php

declare(strict_types=1);

namespace App\Application\Services\Ticket;

use App\Application\Contracts\Repositories\Employee\EmployeeRepositoryInterface;
use App\Application\Contracts\Repositories\Ticket\TicketCommentRepositoryInterface;
use App\Application\Contracts\Repositories\Ticket\TicketRepositoryInterface;
use App\Application\DTOs\Ticket\CreateTicketCommentData;
use App\Application\Services\Audit\AuditLogService;
use App\Domain\Ticket\TicketComment;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;

final class TicketCommentService
{
    public function __construct(
        private readonly TicketCommentRepositoryInterface $comments,
        private readonly TicketRepositoryInterface $tickets,
        private readonly EmployeeRepositoryInterface $employees,
        private readonly AuditLogService $auditLog,
    ) {}

    public function list(Authenticatable $actor, string $ticketId, int $perPage = 25): CursorPaginator
    {
        $ticket = $this->tickets->findById($ticketId) ?? throw new ModelNotFoundException;
        Gate::forUser($actor)->authorize('view', $ticket);

        $canSeeInternal = Gate::forUser($actor)->allows('addInternalNote', $ticket);

        return $this->comments->paginateForTicket($ticketId, $canSeeInternal, $perPage);
    }

    public function create(Authenticatable $actor, string $ticketId, CreateTicketCommentData $data): TicketComment
    {
        $ticket = $this->tickets->findById($ticketId) ?? throw new ModelNotFoundException;

        $ability = $data->isInternal ? 'addInternalNote' : 'addComment';
        Gate::forUser($actor)->authorize($ability, $ticket);

        $authorEmployee = $this->employees->findByUserId($actor->getAuthIdentifier());

        $comment = $this->comments->create([
            'ticket_id' => $ticketId,
            'author_employee_id' => $authorEmployee?->id,
            'body' => $data->body,
            'is_internal' => $data->isInternal,
        ]);

        $this->auditLog->record(
            $actor,
            $data->isInternal ? 'ticket.internal_note_added' : 'ticket.comment_added',
            'ticket',
            $ticketId,
            ['comment_id' => $comment->id]
        );

        return $comment;
    }
}
