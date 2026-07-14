<?php

declare(strict_types=1);

use App\Domain\Automation\ConditionEvaluator;

it('resolves a nested dot-path field from context', function () {
    $context = ['ticket' => ['priority' => 'critical']];

    expect(ConditionEvaluator::resolveField($context, 'ticket.priority'))->toBe('critical');
});

it('returns null for a missing field path', function () {
    $context = ['ticket' => ['priority' => 'critical']];

    expect(ConditionEvaluator::resolveField($context, 'ticket.department'))->toBeNull();
    expect(ConditionEvaluator::resolveField($context, 'employee.name'))->toBeNull();
});

it('evaluates equals', function () {
    $context = ['ticket' => ['priority' => 'critical']];

    expect(ConditionEvaluator::evaluate(['field' => 'ticket.priority', 'operator' => 'equals', 'value' => 'critical'], $context))->toBeTrue();
    expect(ConditionEvaluator::evaluate(['field' => 'ticket.priority', 'operator' => 'equals', 'value' => 'low'], $context))->toBeFalse();
});

it('evaluates not_equals', function () {
    $context = ['ticket' => ['priority' => 'critical']];

    expect(ConditionEvaluator::evaluate(['field' => 'ticket.priority', 'operator' => 'not_equals', 'value' => 'low'], $context))->toBeTrue();
});

it('evaluates contains', function () {
    $context = ['ticket' => ['subject' => 'Server is down']];

    expect(ConditionEvaluator::evaluate(['field' => 'ticket.subject', 'operator' => 'contains', 'value' => 'down'], $context))->toBeTrue();
    expect(ConditionEvaluator::evaluate(['field' => 'ticket.subject', 'operator' => 'contains', 'value' => 'up'], $context))->toBeFalse();
});

it('evaluates greater_than and less_than numerically', function () {
    $context = ['order' => ['total' => 100]];

    expect(ConditionEvaluator::evaluate(['field' => 'order.total', 'operator' => 'greater_than', 'value' => 50], $context))->toBeTrue();
    expect(ConditionEvaluator::evaluate(['field' => 'order.total', 'operator' => 'less_than', 'value' => 50], $context))->toBeFalse();
});

it('returns false for an unknown operator', function () {
    $context = ['ticket' => ['priority' => 'critical']];

    expect(ConditionEvaluator::evaluate(['field' => 'ticket.priority', 'operator' => 'bogus', 'value' => 'critical'], $context))->toBeFalse();
});
