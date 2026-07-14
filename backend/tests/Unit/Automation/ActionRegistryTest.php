<?php

declare(strict_types=1);

use App\Application\Services\Automation\ActionRegistry;
use App\Domain\Shared\Exceptions\UnknownAutomationActionException;
use Tests\Support\AlwaysFailsAutomationAction;

it('lists registered action names', function () {
    $registry = new ActionRegistry([new AlwaysFailsAutomationAction]);

    expect($registry->names())->toBe(['always_fails']);
    expect($registry->has('always_fails'))->toBeTrue();
    expect($registry->has('does_not_exist'))->toBeFalse();
});

it('dispatches execution to the matching action', function () {
    $registry = new ActionRegistry([new AlwaysFailsAutomationAction]);

    expect(fn () => $registry->execute('always_fails', [], []))
        ->toThrow(RuntimeException::class, 'Deliberate test failure.');
});

it('throws UnknownAutomationActionException for an unregistered action name', function () {
    $registry = new ActionRegistry([]);

    expect(fn () => $registry->execute('does_not_exist', [], []))
        ->toThrow(UnknownAutomationActionException::class);
});
