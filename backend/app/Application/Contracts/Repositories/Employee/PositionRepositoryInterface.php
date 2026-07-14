<?php

declare(strict_types=1);

namespace App\Application\Contracts\Repositories\Employee;

use App\Domain\Employee\Position;
use Illuminate\Contracts\Pagination\CursorPaginator;

interface PositionRepositoryInterface
{
    public function paginate(int $perPage = 25): CursorPaginator;

    public function findById(string $id): ?Position;

    public function titleExists(string $title, ?string $exceptId = null): bool;

    /**
     * @param  array{title: string, description: ?string}  $attributes
     */
    public function create(array $attributes): Position;

    /**
     * @param  array{title: string, description: ?string}  $attributes
     */
    public function update(string $id, array $attributes): Position;

    public function delete(string $id): void;
}
