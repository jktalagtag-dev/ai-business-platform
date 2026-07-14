<?php

declare(strict_types=1);

namespace App\Application\Services\Employee;

use App\Application\Contracts\Repositories\Employee\EmployeeNoteRepositoryInterface;
use App\Application\Contracts\Repositories\Employee\EmployeeRepositoryInterface;
use App\Application\DTOs\Employee\CreateEmployeeNoteData;
use App\Application\Services\Audit\AuditLogService;
use App\Domain\Employee\EmployeeNote;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;

final class EmployeeNoteService
{
    public function __construct(
        private readonly EmployeeNoteRepositoryInterface $notes,
        private readonly EmployeeRepositoryInterface $employees,
        private readonly AuditLogService $auditLog,
    ) {}

    public function list(Authenticatable $actor, string $employeeId, int $perPage = 25): CursorPaginator
    {
        $employee = $this->employees->findById($employeeId) ?? throw new ModelNotFoundException;
        Gate::forUser($actor)->authorize('view', $employee);

        return $this->notes->paginateForEmployee($employeeId, $perPage);
    }

    public function create(Authenticatable $actor, string $employeeId, CreateEmployeeNoteData $data): EmployeeNote
    {
        $employee = $this->employees->findById($employeeId) ?? throw new ModelNotFoundException;
        Gate::forUser($actor)->authorize('addNote', $employee);

        $note = $this->notes->create([
            'employee_id' => $employeeId,
            'author_user_id' => $actor->getAuthIdentifier(),
            'note' => $data->note,
        ]);

        $this->auditLog->record($actor, 'employee.note_added', 'employee', $employeeId, [
            'note_id' => $note->id,
        ]);

        return $note;
    }
}
