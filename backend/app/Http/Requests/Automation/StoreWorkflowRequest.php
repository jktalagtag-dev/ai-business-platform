<?php

declare(strict_types=1);

namespace App\Http\Requests\Automation;

use App\Application\Services\Automation\ActionRegistry;
use App\Domain\Automation\ConditionEvaluator;
use Cron\CronExpression;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreWorkflowRequest extends FormRequest
{
    /**
     * The only event keys AutomationEventSubscriber currently understands
     * — kept in sync with its listen() registrations.
     */
    public const EVENT_TRIGGERS = [
        'ticket.created',
        'ticket.assigned',
        'ticket.status_changed',
        'employee.created',
        'employee.updated',
        'employee.archived',
    ];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'steps' => ['required', 'array', 'min:2'],
            'steps.*.type' => ['required', 'string', Rule::in(['trigger', 'condition', 'action'])],
            'steps.*.config' => ['required', 'array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $steps = $this->input('steps', []);

            if (! is_array($steps) || $steps === []) {
                return;
            }

            foreach ($steps as $index => $step) {
                $isFirst = $index === 0;
                $type = $step['type'] ?? null;

                if ($isFirst && $type !== 'trigger') {
                    $validator->errors()->add('steps.0.type', 'The first step must be a trigger.');
                } elseif (! $isFirst && $type === 'trigger') {
                    $validator->errors()->add("steps.{$index}.type", 'Only the first step may be a trigger.');
                }
            }

            if (($steps[0]['type'] ?? null) === 'trigger') {
                $this->validateTriggerConfig($validator, (array) ($steps[0]['config'] ?? []));
            }

            $hasAction = false;

            foreach ($steps as $index => $step) {
                $config = (array) ($step['config'] ?? []);

                if (($step['type'] ?? null) === 'condition') {
                    $this->validateConditionConfig($validator, $index, $config);
                }

                if (($step['type'] ?? null) === 'action') {
                    $hasAction = true;
                    $this->validateActionConfig($validator, $index, $config);
                }
            }

            if (! $hasAction) {
                $validator->errors()->add('steps', 'A workflow must have at least one action step.');
            }
        });
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function validateTriggerConfig(Validator $validator, array $config): void
    {
        $kind = $config['kind'] ?? null;

        if (! in_array($kind, ['event', 'schedule'], true)) {
            $validator->errors()->add('steps.0.config.kind', 'kind must be "event" or "schedule".');

            return;
        }

        if ($kind === 'event') {
            if (! in_array($config['event'] ?? null, self::EVENT_TRIGGERS, true)) {
                $validator->errors()->add('steps.0.config.event', 'Unknown event trigger.');
            }

            return;
        }

        $cron = $config['cron'] ?? null;

        if (! is_string($cron) || ! $this->isValidCron($cron)) {
            $validator->errors()->add('steps.0.config.cron', 'Invalid cron expression.');
        }
    }

    private function isValidCron(string $cron): bool
    {
        try {
            new CronExpression($cron);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function validateConditionConfig(Validator $validator, int $index, array $config): void
    {
        if (! isset($config['field'], $config['operator']) || ! array_key_exists('value', $config)) {
            $validator->errors()->add("steps.{$index}.config", 'A condition requires field, operator, and value.');

            return;
        }

        if (! in_array($config['operator'], ConditionEvaluator::OPERATORS, true)) {
            $validator->errors()->add("steps.{$index}.config.operator", 'Unknown operator.');
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function validateActionConfig(Validator $validator, int $index, array $config): void
    {
        $actionName = $config['action'] ?? null;
        $registry = app(ActionRegistry::class);

        if (! is_string($actionName) || ! $registry->has($actionName)) {
            $validator->errors()->add("steps.{$index}.config.action", 'Unknown action.');
        }
    }
}
