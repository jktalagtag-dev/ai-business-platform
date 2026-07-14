<?php

declare(strict_types=1);

namespace App\Application\Contracts\Repositories\Ticket;

use App\Domain\Ticket\TicketComment;
use Illuminate\Contracts\Pagination\CursorPaginator;

interface TicketCommentRepositoryInterface
{
    public function paginateForTicket(string $ticketId, bool $includeInternal, int $perPage = 25): CursorPaginator;

    /**
     * @param  array{ticket_id: string, author_employee_id: ?string, body: string, is_internal: bool}  $attributes
     */
    public function create(array $attributes): TicketComment;
}
