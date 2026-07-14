<?php

declare(strict_types=1);

use App\Application\Jobs\Ticket\EscalationReminderJob;
use App\Application\Jobs\Ticket\StatusChangeNotificationJob;
use App\Application\Jobs\Ticket\TicketAssignmentNotificationJob;
use Illuminate\Support\Facades\Queue;

it('dispatches an escalation reminder job when a critical ticket is created', function () {
    Queue::fake();
    $session = ownerSession();
    $requester = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $requester['user_id']]);

    asToken($requester['token'])->postJson('/api/v1/tickets', createTicketPayload(['priority' => 'critical']))->assertCreated();

    Queue::assertPushedOn('notifications', EscalationReminderJob::class);
});

it('does not dispatch an escalation reminder job for a non-critical ticket', function () {
    Queue::fake();
    $session = ownerSession();
    $requester = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $requester['user_id']]);

    asToken($requester['token'])->postJson('/api/v1/tickets', createTicketPayload(['priority' => 'medium']))->assertCreated();

    Queue::assertNotPushed(EscalationReminderJob::class);
});

it('dispatches a ticket assignment notification job when a technician is assigned', function () {
    Queue::fake();
    $session = ownerSession();
    $requester = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    $technician = tokenForRoleWithUser($session['tenant_id'], 'Member', 'tech@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $requester['user_id']]);
    $technicianEmployeeId = createEmployeeRecord($session['tenant_id'], ['user_id' => $technician['user_id']])->id;

    $ticketId = asToken($requester['token'])->postJson('/api/v1/tickets', createTicketPayload())->json('data.id');

    asToken($session['token'])->postJson("/api/v1/tickets/{$ticketId}/assign", [
        'technician_employee_id' => $technicianEmployeeId,
    ])->assertOk();

    Queue::assertPushedOn('notifications', TicketAssignmentNotificationJob::class);
});

it('dispatches a status change notification job when status changes', function () {
    Queue::fake();
    $session = ownerSession();
    $requester = tokenForRoleWithUser($session['tenant_id'], 'Member', 'requester@example.com');
    createEmployeeRecord($session['tenant_id'], ['user_id' => $requester['user_id']]);

    $ticketId = asToken($requester['token'])->postJson('/api/v1/tickets', createTicketPayload())->json('data.id');

    asToken($session['token'])->patchJson("/api/v1/tickets/{$ticketId}/status", ['status' => 'in_progress'])->assertOk();

    Queue::assertPushedOn('notifications', StatusChangeNotificationJob::class);
});
