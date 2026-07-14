<?php

declare(strict_types=1);

namespace App\Application\Contracts\Repositories\KnowledgeBase;

use App\Domain\KnowledgeBase\Document;
use Illuminate\Contracts\Pagination\CursorPaginator;

interface DocumentRepositoryInterface
{
    public function paginateForTenant(int $perPage = 25): CursorPaginator;

    public function findById(string $id): ?Document;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Document;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(string $id, array $attributes): Document;

    public function delete(string $id): void;
}
