<?php

declare(strict_types=1);

namespace App\Application\Contracts\Repositories\Ticket;

use App\Domain\Ticket\TicketAttachment;
use Illuminate\Contracts\Pagination\CursorPaginator;

interface TicketAttachmentRepositoryInterface
{
    public function paginateForTicket(string $ticketId, int $perPage = 25): CursorPaginator;

    /**
     * @param  array{ticket_id: string, uploaded_by_employee_id: ?string, file_path: string, original_filename: string, mime_type: string, size_bytes: int}  $attributes
     */
    public function create(array $attributes): TicketAttachment;
}
