<?php

declare(strict_types=1);

namespace App\Application\Contracts\Repositories\Ai;

use App\Domain\Ai\AiConversation;
use Illuminate\Contracts\Pagination\CursorPaginator;

interface AiConversationRepositoryInterface
{
    public function paginateForUser(string $userId, int $perPage = 25): CursorPaginator;

    public function findById(string $id): ?AiConversation;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): AiConversation;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(string $id, array $attributes): AiConversation;

    public function delete(string $id): void;
}
