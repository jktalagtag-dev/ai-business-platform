<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Ticket;

use App\Application\DTOs\Ticket\CreateTicketCommentData;
use App\Application\Services\Ticket\TicketCommentService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\StoreTicketCommentRequest;
use App\Http\Resources\Ticket\TicketCommentResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;

#[OAT\Tag(name: 'Ticket Comments', description: 'Comments and internal notes on a ticket')]
final class TicketCommentController extends Controller
{
    public function __construct(private readonly TicketCommentService $comments) {}

    #[OAT\Get(
        path: '/api/v1/tickets/{ticket}/comments',
        tags: ['Ticket Comments'],
        summary: 'List comments for a ticket. Internal notes are only included for staff with addInternalNote rights.',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'ticket', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        responses: [new OAT\Response(response: 200, description: 'Comments returned')]
    )]
    public function index(Request $request, string $ticket): JsonResponse
    {
        $perPage = max(min((int) $request->query('per_page', 25), 100), 1);
        $paginator = $this->comments->list($request->user(), $ticket, $perPage);
        $items = collect($paginator->items())->map(fn ($c) => new TicketCommentResource($c));

        return ApiResponse::paginated($items, $paginator);
    }

    #[OAT\Post(
        path: '/api/v1/tickets/{ticket}/comments',
        tags: ['Ticket Comments'],
        summary: 'Add a comment. Set is_internal=true for a technician/admin-only note (requester cannot).',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'ticket', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        requestBody: new OAT\RequestBody(
            required: true,
            content: new OAT\JsonContent(
                required: ['body'],
                properties: [
                    new OAT\Property(property: 'body', type: 'string'),
                    new OAT\Property(property: 'is_internal', type: 'boolean', nullable: true),
                ]
            )
        ),
        responses: [
            new OAT\Response(response: 201, description: 'Comment created'),
            new OAT\Response(response: 422, description: 'Validation failed'),
        ]
    )]
    public function store(StoreTicketCommentRequest $request, string $ticket): JsonResponse
    {
        $comment = $this->comments->create($request->user(), $ticket, new CreateTicketCommentData(
            body: $request->string('body')->toString(),
            isInternal: $request->boolean('is_internal'),
        ));

        return ApiResponse::success(new TicketCommentResource($comment), status: 201);
    }
}
