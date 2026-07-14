<?php

declare(strict_types=1);

namespace App\Application\Listeners\Automation;

use App\Application\Events\Employee\EmployeeArchived;
use App\Application\Events\Employee\EmployeeCreated;
use App\Application\Events\Employee\EmployeeUpdated;
use App\Application\Events\Ticket\TicketAssigned;
use App\Application\Events\Ticket\TicketCreated;
use App\Application\Events\Ticket\TicketStatusChanged;
use App\Application\Services\Automation\WorkflowMatchingService;
use App\Domain\Employee\Employee;
use App\Domain\Ticket\Ticket;
use Illuminate\Events\Dispatcher;

/**
 * Cross-cutting: subscribes to every event this engine currently
 * understands, rather than each module registering a per-event listener
 * itself — registered via Event::subscribe() from AutomationServiceProvider
 * alone, so adding this engine required zero edits to the Ticket/Employee
 * modules' own service providers. Extending trigger coverage to a new
 * event later means adding one method + one listen() line here, not
 * touching the event's own module.
 */
final class AutomationEventSubscriber
{
    public function __construct(private readonly WorkflowMatchingService $matcher) {}

    public function handleTicketCreated(TicketCreated $event): void
    {
        $this->matcher->handle('ticket.created', $this->ticketContext($event->ticket));
    }

    public function handleTicketAssigned(TicketAssigned $event): void
    {
        $context = $this->ticketContext($event->ticket);
        $context['previous_technician_employee_id'] = $event->previousTechnicianEmployeeId;

        $this->matcher->handle('ticket.assigned', $context);
    }

    public function handleTicketStatusChanged(TicketStatusChanged $event): void
    {
        $context = $this->ticketContext($event->ticket);
        $context['previous_status'] = $event->previousStatus;

        $this->matcher->handle('ticket.status_changed', $context);
    }

    public function handleEmployeeCreated(EmployeeCreated $event): void
    {
        $this->matcher->handle('employee.created', $this->employeeContext($event->employee));
    }

    public function handleEmployeeUpdated(EmployeeUpdated $event): void
    {
        $this->matcher->handle('employee.updated', $this->employeeContext($event->employee));
    }

    public function handleEmployeeArchived(EmployeeArchived $event): void
    {
        $this->matcher->handle('employee.archived', $this->employeeContext($event->employee));
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(TicketCreated::class, [self::class, 'handleTicketCreated']);
        $events->listen(TicketAssigned::class, [self::class, 'handleTicketAssigned']);
        $events->listen(TicketStatusChanged::class, [self::class, 'handleTicketStatusChanged']);
        $events->listen(EmployeeCreated::class, [self::class, 'handleEmployeeCreated']);
        $events->listen(EmployeeUpdated::class, [self::class, 'handleEmployeeUpdated']);
        $events->listen(EmployeeArchived::class, [self::class, 'handleEmployeeArchived']);
    }

    /**
     * @return array{ticket: array<string, mixed>}
     */
    private function ticketContext(Ticket $ticket): array
    {
        return [
            'ticket' => [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticketNumber,
                'type' => $ticket->type,
                'priority' => $ticket->priority,
                'status' => $ticket->status,
                'subject' => $ticket->subject,
                'employee_id' => $ticket->employeeId,
                'assigned_technician_id' => $ticket->assignedTechnicianId,
                'department_id' => $ticket->departmentId,
            ],
        ];
    }

    /**
     * @return array{employee: array<string, mixed>}
     */
    private function employeeContext(Employee $employee): array
    {
        return [
            'employee' => [
                'id' => $employee->id,
                'employee_number' => $employee->employeeNumber,
                'first_name' => $employee->firstName,
                'last_name' => $employee->lastName,
                'email' => $employee->email,
                'department_id' => $employee->departmentId,
                'employment_status' => $employee->employmentStatus,
            ],
        ];
    }
}
