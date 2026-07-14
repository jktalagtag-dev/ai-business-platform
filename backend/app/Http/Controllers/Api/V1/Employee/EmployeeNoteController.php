<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Employee;

use App\Application\DTOs\Employee\CreateEmployeeNoteData;
use App\Application\Services\Employee\EmployeeNoteService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeNoteRequest;
use App\Http\Resources\Employee\EmployeeNoteResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;

#[OAT\Tag(name: 'Employees / Notes', description: 'Internal notes about an employee')]
final class EmployeeNoteController extends Controller
{
    public function __construct(private readonly EmployeeNoteService $notes) {}

    #[OAT\Get(
        path: '/api/v1/employees/{employee}/notes',
        tags: ['Employees / Notes'],
        summary: 'List notes for an employee',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'employee', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        responses: [new OAT\Response(response: 200, description: 'Notes returned')]
    )]
    public function index(Request $request, string $employee): JsonResponse
    {
        $paginator = $this->notes->list($request->user(), $employee);
        $items = collect($paginator->items())->map(fn ($n) => new EmployeeNoteResource($n));

        return ApiResponse::paginated($items, $paginator);
    }

    #[OAT\Post(
        path: '/api/v1/employees/{employee}/notes',
        tags: ['Employees / Notes'],
        summary: 'Add a note to an employee',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'employee', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        requestBody: new OAT\RequestBody(
            required: true,
            content: new OAT\JsonContent(required: ['note'], properties: [new OAT\Property(property: 'note', type: 'string')])
        ),
        responses: [
            new OAT\Response(response: 201, description: 'Note created'),
            new OAT\Response(response: 403, description: 'Not authorized to add notes for this employee'),
        ]
    )]
    public function store(StoreEmployeeNoteRequest $request, string $employee): JsonResponse
    {
        $note = $this->notes->create($request->user(), $employee, new CreateEmployeeNoteData(
            note: $request->string('note')->toString(),
        ));

        return ApiResponse::success(new EmployeeNoteResource($note), status: 201);
    }
}
