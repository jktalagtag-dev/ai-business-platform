<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Audit;

use App\Application\Services\Audit\AuditLogService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Audit\AuditLogResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;

#[OAT\Tag(name: 'Audit Logs', description: 'Read-only audit trail, owner/admin only')]
final class AuditLogController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    #[OAT\Get(
        path: '/api/v1/audit-logs',
        tags: ['Audit Logs'],
        summary: 'List audit log entries. Requires the owner or admin role.',
        security: [['sanctum' => []]],
        parameters: [
            new OAT\Parameter(name: 'subject_type', in: 'query', schema: new OAT\Schema(type: 'string')),
            new OAT\Parameter(name: 'subject_id', in: 'query', schema: new OAT\Schema(type: 'string')),
        ],
        responses: [
            new OAT\Response(response: 200, description: 'Audit log entries returned'),
            new OAT\Response(response: 403, description: 'Caller does not hold the owner or admin role'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $filters = array_filter([
            'subject_type' => $request->query('subject_type'),
            'subject_id' => $request->query('subject_id'),
        ], fn ($v) => $v !== null);

        $paginator = $this->auditLog->list($filters);
        $items = collect($paginator->items())->map(fn ($log) => new AuditLogResource($log));

        return ApiResponse::paginated($items, $paginator);
    }
}
