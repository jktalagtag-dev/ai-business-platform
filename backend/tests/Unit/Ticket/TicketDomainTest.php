<?php

declare(strict_types=1);

use App\Domain\Ticket\Ticket;

function makeTicket(array $overrides = []): Ticket
{
    $attributes = array_merge([
        'id' => '01H0000000000000000000001',
        'tenantId' => '01H0000000000000000000002',
        'ticketNumber' => 'TCK-000001',
        'employeeId' => '01H0000000000000000000003',
        'assignedTechnicianId' => null,
        'departmentId' => null,
        'type' => 'hardware',
        'priority' => 'medium',
        'status' => 'open',
        'subject' => 'Test subject',
        'description' => 'Test description',
        'resolutionNotes' => null,
        'resolvedAt' => null,
        'closedAt' => null,
        'slaBreachedAt' => null,
        'createdAt' => new DateTimeImmutable,
    ], $overrides);

    return new Ticket(...$attributes);
}

it('is not assigned when no technician is set', function () {
    expect(makeTicket()->isAssigned())->toBeFalse();
});

it('is assigned once a technician is set', function () {
    expect(makeTicket(['assignedTechnicianId' => '01H0000000000000000000004'])->isAssigned())->toBeTrue();
});

it('is open for any non-terminal status', function () {
    expect(makeTicket(['status' => 'in_progress'])->isOpen())->toBeTrue();
});

it('is not open once resolved, closed, or cancelled', function (string $status) {
    expect(makeTicket(['status' => $status])->isOpen())->toBeFalse();
})->with(['resolved', 'closed', 'cancelled']);

it('is critical only at critical priority', function () {
    expect(makeTicket(['priority' => 'critical'])->isCritical())->toBeTrue();
    expect(makeTicket(['priority' => 'high'])->isCritical())->toBeFalse();
});
