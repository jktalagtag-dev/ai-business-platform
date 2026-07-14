<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories\Ticket;

use App\Application\Contracts\Repositories\Ticket\TicketAttachmentRepositoryInterface;
use App\Application\Contracts\Services\TenantContextInterface;
use App\Domain\Ticket\TicketAttachment as TicketAttachmentEntity;
use App\Http\Support\CachedCursorPaginator;
use App\Infrastructure\Persistence\Eloquent\Models\Ticket\TicketAttachment;
use Illuminate\Contracts\Pagination\CursorPaginator;

final class TicketAttachmentRepository implements TicketAttachmentRepositoryInterface
{
    public function __construct(private readonly TenantContextInterface $tenantContext) {}

    public function paginateForTicket(string $ticketId, int $perPage = 25): CursorPaginator
    {
        $query = TicketAttachment::where('tenant_id', $this->tenantContext->tenantId())
            ->where('ticket_id', $ticketId)
            ->orderByDesc('created_at')
            ->cursorPaginate($perPage);

        return CachedCursorPaginator::wrap($query, fn (TicketAttachment $a): TicketAttachmentEntity => $this->toDomain($a));
    }

    public function create(array $attributes): TicketAttachmentEntity
    {
        $attachment = TicketAttachment::create(array_merge($attributes, [
            'tenant_id' => $this->tenantContext->tenantId(),
            'created_at' => now(),
        ]));

        return $this->toDomain($attachment);
    }

    private function toDomain(TicketAttachment $attachment): TicketAttachmentEntity
    {
        return new TicketAttachmentEntity(
            id: $attachment->id,
            tenantId: $attachment->tenant_id,
            ticketId: $attachment->ticket_id,
            uploadedByEmployeeId: $attachment->uploaded_by_employee_id,
            filePath: $attachment->file_path,
            originalFilename: $attachment->original_filename,
            mimeType: $attachment->mime_type,
            sizeBytes: $attachment->size_bytes,
            createdAt: \DateTimeImmutable::createFromInterface($attachment->created_at),
        );
    }
}
