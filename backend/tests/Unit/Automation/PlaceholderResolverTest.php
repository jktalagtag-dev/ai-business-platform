<?php

declare(strict_types=1);

use App\Domain\Automation\PlaceholderResolver;

it('substitutes a single placeholder', function () {
    $context = ['ticket' => ['subject' => 'Server down']];

    expect(PlaceholderResolver::render('New ticket: {{ticket.subject}}', $context))
        ->toBe('New ticket: Server down');
});

it('substitutes multiple placeholders', function () {
    $context = ['ticket' => ['ticket_number' => 'TCK-000123', 'priority' => 'critical']];

    expect(PlaceholderResolver::render('{{ticket.ticket_number}} is {{ticket.priority}}', $context))
        ->toBe('TCK-000123 is critical');
});

it('renders a missing placeholder as an empty string rather than throwing', function () {
    expect(PlaceholderResolver::render('Value: {{does.not.exist}}', []))->toBe('Value: ');
});

it('recursively renders every string value in a config array', function () {
    $context = ['ticket' => ['subject' => 'Server down', 'priority' => 'critical']];
    $config = [
        'action' => 'send_notification',
        'to' => 'ops@example.com',
        'subject' => 'Ticket: {{ticket.subject}}',
        'nested' => ['message' => 'Priority: {{ticket.priority}}'],
    ];

    $rendered = PlaceholderResolver::renderConfig($config, $context);

    expect($rendered['subject'])->toBe('Ticket: Server down');
    expect($rendered['nested']['message'])->toBe('Priority: critical');
    expect($rendered['to'])->toBe('ops@example.com');
});
