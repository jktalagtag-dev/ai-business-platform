<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories\Employee;

use App\Application\Contracts\Repositories\Employee\EmployeeNoteRepositoryInterface;
use App\Application\Contracts\Services\TenantContextInterface;
use App\Domain\Employee\EmployeeNote as EmployeeNoteEntity;
use App\Http\Support\CachedCursorPaginator;
use App\Infrastructure\Persistence\Eloquent\Models\Employee\EmployeeNote;
use Illuminate\Contracts\Pagination\CursorPaginator;

final class EmployeeNoteRepository implements EmployeeNoteRepositoryInterface
{
    public function __construct(private readonly TenantContextInterface $tenantContext) {}

    public function paginateForEmployee(string $employeeId, int $perPage = 25): CursorPaginator
    {
        $paginator = EmployeeNote::where('tenant_id', $this->tenantContext->tenantId())
            ->where('employee_id', $employeeId)
            ->orderByDesc('created_at')
            ->cursorPaginate($perPage);

        return CachedCursorPaginator::wrap($paginator, fn (EmployeeNote $n): EmployeeNoteEntity => $this->toDomain($n));
    }

    public function create(array $attributes): EmployeeNoteEntity
    {
        $note = EmployeeNote::create(array_merge($attributes, [
            'tenant_id' => $this->tenantContext->tenantId(),
        ]));

        return $this->toDomain($note);
    }

    private function toDomain(EmployeeNote $note): EmployeeNoteEntity
    {
        return new EmployeeNoteEntity(
            id: $note->id,
            tenantId: $note->tenant_id,
            employeeId: $note->employee_id,
            authorUserId: $note->author_user_id,
            note: $note->note,
            createdAt: \DateTimeImmutable::createFromInterface($note->created_at),
        );
    }
}
