<?php

declare(strict_types=1);

namespace App\Application\Services\Ticket;

use App\Application\Contracts\Repositories\Employee\EmployeeRepositoryInterface;
use App\Application\Contracts\Repositories\Ticket\TicketAttachmentRepositoryInterface;
use App\Application\Contracts\Repositories\Ticket\TicketRepositoryInterface;
use App\Application\Services\Audit\AuditLogService;
use App\Domain\Ticket\TicketAttachment;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;

final class TicketAttachmentService
{
    public function __construct(
        private readonly TicketAttachmentRepositoryInterface $attachments,
        private readonly TicketRepositoryInterface $tickets,
        private readonly EmployeeRepositoryInterface $employees,
        private readonly AuditLogService $auditLog,
    ) {}

    public function list(Authenticatable $actor, string $ticketId, int $perPage = 25): CursorPaginator
    {
        $ticket = $this->tickets->findById($ticketId) ?? throw new ModelNotFoundException;
        Gate::forUser($actor)->authorize('view', $ticket);

        return $this->attachments->paginateForTicket($ticketId, $perPage);
    }

    public function upload(
        Authenticatable $actor,
        string $ticketId,
        string $storedPath,
        string $originalFilename,
        string $mimeType,
        int $sizeBytes,
    ): TicketAttachment {
        $ticket = $this->tickets->findById($ticketId) ?? throw new ModelNotFoundException;
        Gate::forUser($actor)->authorize('addComment', $ticket);

        $uploader = $this->employees->findByUserId($actor->getAuthIdentifier());

        $attachment = $this->attachments->create([
            'ticket_id' => $ticketId,
            'uploaded_by_employee_id' => $uploader?->id,
            'file_path' => $storedPath,
            'original_filename' => $originalFilename,
            'mime_type' => $mimeType,
            'size_bytes' => $sizeBytes,
        ]);

        $this->auditLog->record($actor, 'ticket.attachment_added', 'ticket', $ticketId, [
            'attachment_id' => $attachment->id,
            'original_filename' => $originalFilename,
        ]);

        return $attachment;
    }
}
