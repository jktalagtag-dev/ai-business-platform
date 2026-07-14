<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Ticket;

use App\Application\Contracts\Repositories\Employee\EmployeeRepositoryInterface;
use App\Application\DTOs\Ticket\CreateTicketData;
use App\Application\DTOs\Ticket\UpdateTicketData;
use App\Application\Services\Ticket\TicketService;
use App\Application\Services\Ticket\TicketStatisticsService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\AssignTicketRequest;
use App\Http\Requests\Ticket\CloseTicketRequest;
use App\Http\Requests\Ticket\ReopenTicketRequest;
use App\Http\Requests\Ticket\StoreTicketRequest;
use App\Http\Requests\Ticket\UpdateTicketRequest;
use App\Http\Requests\Ticket\UpdateTicketStatusRequest;
use App\Http\Resources\Ticket\TicketResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;

#[OAT\Tag(name: 'Tickets', description: 'IT support tickets')]
final class TicketController extends Controller
{
    public function __construct(
        private readonly TicketService $tickets,
        private readonly TicketStatisticsService $statistics,
        private readonly EmployeeRepositoryInterface $employees,
    ) {}

    #[OAT\Get(
        path: '/api/v1/tickets',
        tags: ['Tickets'],
        summary: 'List tickets — full directory for Owner/Admin, department-scoped for managers, self-scoped otherwise',
        security: [['sanctum' => []]],
        parameters: [
            new OAT\Parameter(name: 'employee_id', in: 'query', schema: new OAT\Schema(type: 'string')),
            new OAT\Parameter(name: 'ticket_number', in: 'query', schema: new OAT\Schema(type: 'string')),
            new OAT\Parameter(name: 'status', in: 'query', schema: new OAT\Schema(type: 'string')),
            new OAT\Parameter(name: 'priority', in: 'query', schema: new OAT\Schema(type: 'string')),
            new OAT\Parameter(name: 'department_id', in: 'query', schema: new OAT\Schema(type: 'string')),
            new OAT\Parameter(name: 'assigned_technician_id', in: 'query', schema: new OAT\Schema(type: 'string')),
            new OAT\Parameter(name: 'search', in: 'query', schema: new OAT\Schema(type: 'string')),
            new OAT\Parameter(name: 'date_from', in: 'query', schema: new OAT\Schema(type: 'string', format: 'date')),
            new OAT\Parameter(name: 'date_to', in: 'query', schema: new OAT\Schema(type: 'string', format: 'date')),
            new OAT\Parameter(name: 'quick_filter', in: 'query', description: 'open | resolved | critical | my_tickets | unassigned', schema: new OAT\Schema(type: 'string')),
        ],
        responses: [new OAT\Response(response: 200, description: 'Tickets returned')]
    )]
    public function index(Request $request): JsonResponse
    {
        $filters = array_filter([
            'employee_id' => $request->query('employee_id'),
            'ticket_number' => $request->query('ticket_number'),
            'status' => $request->query('status'),
            'priority' => $request->query('priority'),
            'department_id' => $request->query('department_id'),
            'assigned_technician_id' => $request->query('assigned_technician_id'),
            'search' => $request->query('search'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'sort' => $request->query('sort'),
            'direction' => $request->query('direction'),
        ], fn ($v) => $v !== null);

        $filters = array_merge($filters, $this->quickFilter($request));

        $perPage = max(min((int) $request->query('per_page', 25), 100), 1);
        $paginator = $this->tickets->list($request->user(), $filters, $perPage);
        $items = collect($paginator->items())->map(fn ($t) => new TicketResource($t));

        return ApiResponse::paginated($items, $paginator);
    }

    /**
     * @return array<string, mixed>
     */
    private function quickFilter(Request $request): array
    {
        $quickFilter = $request->query('quick_filter');

        return match ($quickFilter) {
            'open' => ['status' => 'open'],
            'resolved' => ['status' => 'resolved'],
            'critical' => ['priority' => 'critical'],
            'unassigned' => ['unassigned' => true],
            'my_tickets' => ['my_tickets' => true],
            default => [],
        };
    }

    #[OAT\Post(
        path: '/api/v1/tickets',
        tags: ['Tickets'],
        summary: 'Create a ticket (system-generates the ticket number). Any employee may create one for themselves.',
        security: [['sanctum' => []]],
        requestBody: new OAT\RequestBody(
            required: true,
            content: new OAT\JsonContent(
                required: ['type', 'priority', 'subject', 'description'],
                properties: [
                    new OAT\Property(property: 'employee_id', type: 'string', nullable: true, description: 'Owner/Admin only — defaults to the caller'),
                    new OAT\Property(property: 'type', type: 'string', enum: ['hardware', 'software', 'network', 'account_access', 'printer', 'email', 'security', 'other']),
                    new OAT\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high', 'critical']),
                    new OAT\Property(property: 'subject', type: 'string'),
                    new OAT\Property(property: 'description', type: 'string'),
                ]
            )
        ),
        responses: [
            new OAT\Response(response: 201, description: 'Ticket created'),
            new OAT\Response(response: 422, description: 'Validation failed'),
        ]
    )]
    public function store(StoreTicketRequest $request): JsonResponse
    {
        $employeeId = $request->input('employee_id')
            ?? $this->employees->findByUserId($request->user()->getAuthIdentifier())?->id
            ?? throw new ModelNotFoundException('No employee record is linked to this account.');

        $ticket = $this->tickets->create($request->user(), new CreateTicketData(
            employeeId: $employeeId,
            type: $request->string('type')->toString(),
            priority: $request->string('priority')->toString(),
            subject: $request->string('subject')->toString(),
            description: $request->string('description')->toString(),
        ));

        return ApiResponse::success(new TicketResource($ticket), status: 201);
    }

    #[OAT\Get(
        path: '/api/v1/tickets/statistics',
        tags: ['Tickets'],
        summary: 'Dashboard statistics: open/closed counts, average resolution time, breakdowns by department/priority/technician',
        security: [['sanctum' => []]],
        responses: [new OAT\Response(response: 200, description: 'Statistics returned')]
    )]
    public function statistics(Request $request): JsonResponse
    {
        return ApiResponse::success($this->statistics->statistics($request->user()));
    }

    #[OAT\Get(
        path: '/api/v1/tickets/{ticket}',
        tags: ['Tickets'],
        summary: 'Get a single ticket',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'ticket', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        responses: [
            new OAT\Response(response: 200, description: 'Ticket returned'),
            new OAT\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Request $request, string $ticket): JsonResponse
    {
        return ApiResponse::success(new TicketResource($this->tickets->find($request->user(), $ticket)));
    }

    #[OAT\Patch(
        path: '/api/v1/tickets/{ticket}',
        tags: ['Tickets'],
        summary: 'Update ticket content (type, priority, subject, description, resolution notes)',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'ticket', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        responses: [
            new OAT\Response(response: 200, description: 'Ticket updated'),
            new OAT\Response(response: 422, description: 'Validation failed'),
        ]
    )]
    public function update(UpdateTicketRequest $request, string $ticket): JsonResponse
    {
        $updated = $this->tickets->update($request->user(), $ticket, new UpdateTicketData(
            type: $request->string('type')->toString(),
            priority: $request->string('priority')->toString(),
            subject: $request->string('subject')->toString(),
            description: $request->string('description')->toString(),
            resolutionNotes: $request->input('resolution_notes'),
        ));

        return ApiResponse::success(new TicketResource($updated));
    }

    #[OAT\Post(
        path: '/api/v1/tickets/{ticket}/assign',
        tags: ['Tickets'],
        summary: 'Assign or reassign a technician. Admin only.',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'ticket', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        requestBody: new OAT\RequestBody(
            required: true,
            content: new OAT\JsonContent(required: ['technician_employee_id'], properties: [new OAT\Property(property: 'technician_employee_id', type: 'string')])
        ),
        responses: [
            new OAT\Response(response: 200, description: 'Ticket assigned'),
            new OAT\Response(response: 403, description: 'Missing tickets.manage permission'),
        ]
    )]
    public function assign(AssignTicketRequest $request, string $ticket): JsonResponse
    {
        $updated = $this->tickets->assign($request->user(), $ticket, $request->string('technician_employee_id')->toString());

        return ApiResponse::success(new TicketResource($updated));
    }

    #[OAT\Patch(
        path: '/api/v1/tickets/{ticket}/status',
        tags: ['Tickets'],
        summary: 'Change ticket status (not to/from closed — use /close and /reopen for those)',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'ticket', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        requestBody: new OAT\RequestBody(
            required: true,
            content: new OAT\JsonContent(
                required: ['status'],
                properties: [
                    new OAT\Property(property: 'status', type: 'string', enum: ['open', 'assigned', 'in_progress', 'waiting_for_user', 'resolved', 'cancelled']),
                    new OAT\Property(property: 'note', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OAT\Response(response: 200, description: 'Status changed'),
            new OAT\Response(response: 400, description: 'Invalid transition (e.g. already closed/cancelled)'),
        ]
    )]
    public function updateStatus(UpdateTicketStatusRequest $request, string $ticket): JsonResponse
    {
        $updated = $this->tickets->updateStatus(
            $request->user(),
            $ticket,
            $request->string('status')->toString(),
            $request->input('note')
        );

        return ApiResponse::success(new TicketResource($updated));
    }

    #[OAT\Post(
        path: '/api/v1/tickets/{ticket}/close',
        tags: ['Tickets'],
        summary: 'Close a ticket with resolution notes',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'ticket', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        requestBody: new OAT\RequestBody(
            required: true,
            content: new OAT\JsonContent(required: ['resolution_notes'], properties: [new OAT\Property(property: 'resolution_notes', type: 'string')])
        ),
        responses: [
            new OAT\Response(response: 200, description: 'Ticket closed'),
            new OAT\Response(response: 422, description: 'resolution_notes required'),
        ]
    )]
    public function close(CloseTicketRequest $request, string $ticket): JsonResponse
    {
        $updated = $this->tickets->close($request->user(), $ticket, $request->string('resolution_notes')->toString());

        return ApiResponse::success(new TicketResource($updated));
    }

    #[OAT\Post(
        path: '/api/v1/tickets/{ticket}/reopen',
        tags: ['Tickets'],
        summary: 'Reopen a resolved or closed ticket',
        security: [['sanctum' => []]],
        parameters: [new OAT\Parameter(name: 'ticket', in: 'path', required: true, schema: new OAT\Schema(type: 'string'))],
        responses: [
            new OAT\Response(response: 200, description: 'Ticket reopened'),
            new OAT\Response(response: 400, description: 'Ticket is not in a reopenable state'),
        ]
    )]
    public function reopen(ReopenTicketRequest $request, string $ticket): JsonResponse
    {
        $updated = $this->tickets->reopen($request->user(), $ticket, $request->input('reason'));

        return ApiResponse::success(new TicketResource($updated));
    }
}
