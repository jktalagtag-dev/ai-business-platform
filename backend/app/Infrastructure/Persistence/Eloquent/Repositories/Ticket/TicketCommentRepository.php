<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories\Ticket;

use App\Application\Contracts\Repositories\Ticket\TicketCommentRepositoryInterface;
use App\Application\Contracts\Services\TenantContextInterface;
use App\Domain\Ticket\TicketComment as TicketCommentEntity;
use App\Http\Support\CachedCursorPaginator;
use App\Infrastructure\Persistence\Eloquent\Models\Ticket\TicketComment;
use Illuminate\Contracts\Pagination\CursorPaginator;

final class TicketCommentRepository implements TicketCommentRepositoryInterface
{
    public function __construct(private readonly TenantContextInterface $tenantContext) {}

    public function paginateForTicket(string $ticketId, bool $includeInternal, int $perPage = 25): CursorPaginator
    {
        $query = TicketComment::where('tenant_id', $this->tenantContext->tenantId())
            ->where('ticket_id', $ticketId)
            ->when(! $includeInternal, fn ($q) => $q->where('is_internal', false))
            ->orderBy('created_at')
            ->cursorPaginate($perPage);

        return CachedCursorPaginator::wrap($query, fn (TicketComment $c): TicketCommentEntity => $this->toDomain($c));
    }

    public function create(array $attributes): TicketCommentEntity
    {
        $comment = TicketComment::create(array_merge($attributes, [
            'tenant_id' => $this->tenantContext->tenantId(),
        ]));

        return $this->toDomain($comment);
    }

    private function toDomain(TicketComment $comment): TicketCommentEntity
    {
        return new TicketCommentEntity(
            id: $comment->id,
            tenantId: $comment->tenant_id,
            ticketId: $comment->ticket_id,
            authorEmployeeId: $comment->author_employee_id,
            body: $comment->body,
            isInternal: $comment->is_internal,
            createdAt: \DateTimeImmutable::createFromInterface($comment->created_at),
        );
    }
}
