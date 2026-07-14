<?php

declare(strict_types=1);

namespace App\Application\Contracts\Repositories\Audit;

use Illuminate\Contracts\Pagination\CursorPaginator;

interface AuditLogRepositoryInterface
{
    /**
     * @param  array{subject_type?: string, subject_id?: string}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 25): CursorPaginator;

    /**
     * @param  array{tenant_id: string, actor_user_id: ?string, action: string, subject_type: string, subject_id: string, changes: array<string, mixed>, ip_address: ?string, user_agent: ?string}  $attributes
     */
    public function create(array $attributes): void;
}
