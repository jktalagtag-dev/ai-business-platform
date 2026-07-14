<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\KnowledgeBase;

use App\Application\Services\KnowledgeBase\DocumentService;
use App\Http\Controllers\Controller;
use App\Http\Requests\KnowledgeBase\UploadKnowledgeBaseDocumentRequest;
use App\Http\Resources\KnowledgeBase\DocumentResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;

#[OAT\Tag(name: 'Knowledge Base', description: 'PDF document upload, processing, and management')]
final class DocumentController extends Controller
{
    public function __construct(private readonly DocumentService $documents) {}

    #[OAT\Get(
        path: '/api/v1/knowledge-base/documents',
        tags: ['Knowledge Base'],
        summary: 'List uploaded documents',
        security: [['sanctum' => []]],
        responses: [new OAT\Response(response: 200, description: 'Documents returned')]
    )]
    public function index(Request $request): JsonResponse
    {
        $perPage = max(min((int) $request->query('per_page', 25), 100), 1);
        $paginator = $this->documents->list($request->user(), $perPage);
        $items = collect($paginator->items())->map(fn ($d) => new DocumentResource($d));

        return ApiResponse::paginated($items, $paginator);
    }

    #[OAT\Post(
        path: '/api/v1/knowledge-base/documents',
        tags: ['Knowledge Base'],
        summary: 'Upload a PDF document (multipart/form-data). Processing (extraction, chunking, embedding) runs asynchronously.',
        security: [['sanctum' => []]],
        requestBody: new OAT\RequestBody(
            required: true,
            content: new OAT\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OAT\Schema(required: ['file'], properties: [
                    new OAT\Property(property: 'title', type: 'string', nullable: true, description: 'Defaults to the original filename when omitted'),
                    new OAT\Property(property: 'file', type: 'string', format: 'binary'),
                ])
            )
        ),
        responses: [
            new OAT\Response(response: 201, description: 'Document accepted, status "processing"'),
            new OAT\Response(response: 422, description: 'Validation failed (e.g. not a PDF, too large)'),
            new OAT\Response(response: 403, description: 'Missing knowledge_base.manage permission'),
        ]
    )]
    public function store(UploadKnowledgeBaseDocumentRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $storedPath = $file->store('knowledge-base/documents', 'public');

        $title = $request->input('title') ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        $document = $this->documents->upload(
            $request->user(),
            $title,
            $storedPath,
            $file->getClientOriginalName(),
            $file->getMimeType() ?? 'application/pdf',
            $file->getSize(),
        );

        return ApiResponse::success(new DocumentResource($document), status: 201);
    }

    #[OAT\Get(
        path: '/api/v1/knowledge-base/documents/{document}',
        tags: ['Knowledge Base'],
        summary: 'Get a single document, including its processing status',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'document', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        responses: [
            new OAT\Response(response: 200, description: 'Document returned'),
            new OAT\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Request $request, string $document): JsonResponse
    {
        return ApiResponse::success(new DocumentResource($this->documents->find($request->user(), $document)));
    }

    #[OAT\Delete(
        path: '/api/v1/knowledge-base/documents/{document}',
        tags: ['Knowledge Base'],
        summary: 'Delete a document and its chunks',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'document', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        responses: [
            new OAT\Response(response: 200, description: 'Deleted'),
            new OAT\Response(response: 403, description: 'Missing knowledge_base.manage permission'),
        ]
    )]
    public function destroy(Request $request, string $document): JsonResponse
    {
        $this->documents->delete($request->user(), $document);

        return ApiResponse::success(['message' => 'Document deleted.']);
    }
}
