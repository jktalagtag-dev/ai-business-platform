<?php

declare(strict_types=1);

namespace App\Application\Services\Employee;

use App\Application\Contracts\Repositories\Employee\PositionRepositoryInterface;
use App\Application\DTOs\Employee\CreatePositionData;
use App\Application\DTOs\Employee\UpdatePositionData;
use App\Application\Services\Audit\AuditLogService;
use App\Domain\Employee\Position;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;

final class PositionService
{
    public function __construct(
        private readonly PositionRepositoryInterface $positions,
        private readonly AuditLogService $auditLog,
    ) {}

    public function list(Authenticatable $actor, int $perPage = 25): CursorPaginator
    {
        Gate::forUser($actor)->authorize('viewAny', Position::class);

        return $this->positions->paginate($perPage);
    }

    public function find(Authenticatable $actor, string $id): Position
    {
        $position = $this->positions->findById($id) ?? throw new ModelNotFoundException;

        Gate::forUser($actor)->authorize('view', $position);

        return $position;
    }

    public function create(Authenticatable $actor, CreatePositionData $data): Position
    {
        Gate::forUser($actor)->authorize('create', Position::class);

        $position = $this->positions->create([
            'title' => $data->title,
            'description' => $data->description,
        ]);

        $this->auditLog->record($actor, 'position.created', 'position', $position->id, [
            'title' => $position->title,
        ]);

        return $position;
    }

    public function update(Authenticatable $actor, string $id, UpdatePositionData $data): Position
    {
        $existing = $this->positions->findById($id) ?? throw new ModelNotFoundException;

        Gate::forUser($actor)->authorize('update', $existing);

        $position = $this->positions->update($id, [
            'title' => $data->title,
            'description' => $data->description,
        ]);

        $this->auditLog->record($actor, 'position.updated', 'position', $position->id, [
            'before' => ['title' => $existing->title],
            'after' => ['title' => $position->title],
        ]);

        return $position;
    }

    public function delete(Authenticatable $actor, string $id): void
    {
        $existing = $this->positions->findById($id) ?? throw new ModelNotFoundException;

        Gate::forUser($actor)->authorize('delete', $existing);

        $this->positions->delete($id);

        $this->auditLog->record($actor, 'position.deleted', 'position', $existing->id, [
            'title' => $existing->title,
        ]);
    }
}
