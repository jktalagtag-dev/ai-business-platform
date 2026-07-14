<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Ticket;

use App\Application\Services\Ticket\TicketAttachmentService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\UploadTicketAttachmentRequest;
use App\Http\Resources\Ticket\TicketAttachmentResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;

#[OAT\Tag(name: 'Ticket Attachments', description: 'File attachments on a ticket')]
final class TicketAttachmentController extends Controller
{
    public function __construct(private readonly TicketAttachmentService $attachments) {}

    #[OAT\Get(
        path: '/api/v1/tickets/{ticket}/attachments',
        tags: ['Ticket Attachments'],
        summary: 'List attachments for a ticket',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'ticket', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        responses: [new OAT\Response(response: 200, description: 'Attachments returned')]
    )]
    public function index(Request $request, string $ticket): JsonResponse
    {
        $perPage = max(min((int) $request->query('per_page', 25), 100), 1);
        $paginator = $this->attachments->list($request->user(), $ticket, $perPage);
        $items = collect($paginator->items())->map(fn ($a) => new TicketAttachmentResource($a));

        return ApiResponse::paginated($items, $paginator);
    }

    #[OAT\Post(
        path: '/api/v1/tickets/{ticket}/attachments',
        tags: ['Ticket Attachments'],
        summary: 'Upload an attachment (multipart/form-data, field name "file", max 10MB)',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'ticket', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        requestBody: new OAT\RequestBody(
            required: true,
            content: new OAT\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OAT\Schema(required: ['file'], properties: [
                    new OAT\Property(property: 'file', type: 'string', format: 'binary'),
                ])
            )
        ),
        responses: [
            new OAT\Response(response: 201, description: 'Attachment uploaded'),
            new OAT\Response(response: 422, description: 'Validation failed'),
        ]
    )]
    public function store(UploadTicketAttachmentRequest $request, string $ticket): JsonResponse
    {
        $file = $request->file('file');
        $storedPath = $file->store("tickets/{$ticket}/attachments", 'public');

        $attachment = $this->attachments->upload(
            $request->user(),
            $ticket,
            $storedPath,
            $file->getClientOriginalName(),
            $file->getMimeType() ?? 'application/octet-stream',
            $file->getSize(),
        );

        return ApiResponse::success(new TicketAttachmentResource($attachment), status: 201);
    }
}
