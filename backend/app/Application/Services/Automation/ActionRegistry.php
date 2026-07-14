<?php

declare(strict_types=1);

namespace App\Application\Services\Automation;

use App\Application\Contracts\Services\Automation\AutomationActionInterface;
use App\Domain\Shared\Exceptions\UnknownAutomationActionException;

final class ActionRegistry
{
    /**
     * @var array<string, AutomationActionInterface>
     */
    private array $actions;

    /**
     * @param  list<AutomationActionInterface>  $actions
     */
    public function __construct(array $actions)
    {
        $this->actions = [];

        foreach ($actions as $action) {
            $this->actions[$action->name()] = $action;
        }
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_keys($this->actions);
    }

    public function has(string $name): bool
    {
        return isset($this->actions[$name]);
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function execute(string $name, array $config, array $context): array
    {
        $action = $this->actions[$name] ?? throw new UnknownAutomationActionException("Unknown automation action: {$name}");

        return $action->execute($config, $context);
    }
}
