<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories\Employee;

use App\Application\Contracts\Repositories\Employee\PositionRepositoryInterface;
use App\Application\Contracts\Services\TenantContextInterface;
use App\Domain\Employee\Position as PositionEntity;
use App\Http\Support\CachedCursorPaginator;
use App\Infrastructure\Persistence\Eloquent\Models\Employee\Position;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;

final class PositionRepository implements PositionRepositoryInterface
{
    public function __construct(private readonly TenantContextInterface $tenantContext) {}

    public function paginate(int $perPage = 25): CursorPaginator
    {
        return CachedCursorPaginator::wrap(
            $this->scoped()->orderBy('title')->cursorPaginate($perPage),
            fn (Position $p): PositionEntity => $this->toDomain($p)
        );
    }

    public function findById(string $id): ?PositionEntity
    {
        $position = $this->scoped()->find($id);

        return $position ? $this->toDomain($position) : null;
    }

    public function titleExists(string $title, ?string $exceptId = null): bool
    {
        return $this->scoped()
            ->where('title', $title)
            ->when($exceptId, fn (Builder $q) => $q->where('id', '!=', $exceptId))
            ->exists();
    }

    public function create(array $attributes): PositionEntity
    {
        $position = Position::create(array_merge($attributes, [
            'tenant_id' => $this->tenantContext->tenantId(),
        ]));

        return $this->toDomain($position);
    }

    public function update(string $id, array $attributes): PositionEntity
    {
        $position = $this->scoped()->findOrFail($id);
        $position->fill($attributes)->save();

        return $this->toDomain($position);
    }

    public function delete(string $id): void
    {
        $this->scoped()->findOrFail($id)->delete();
    }

    private function scoped(): Builder
    {
        return Position::where('tenant_id', $this->tenantContext->tenantId());
    }

    private function toDomain(Position $position): PositionEntity
    {
        return new PositionEntity(
            id: $position->id,
            tenantId: $position->tenant_id,
            title: $position->title,
            description: $position->description,
        );
    }
}
