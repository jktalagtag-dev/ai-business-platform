<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Contracts\Repositories\Automation\AutomationJobRepositoryInterface;
use App\Application\Contracts\Repositories\Automation\AutomationJobStepRepositoryInterface;
use App\Application\Contracts\Repositories\Automation\WorkflowRepositoryInterface;
use App\Application\Contracts\Repositories\Automation\WorkflowStepRepositoryInterface;
use App\Application\Listeners\Automation\AutomationEventSubscriber;
use App\Application\Services\Automation\ActionRegistry;
use App\Application\Services\Automation\Actions\LogAuditEventAction;
use App\Application\Services\Automation\Actions\SendNotificationAction;
use App\Domain\Automation\AutomationJob;
use App\Domain\Automation\Workflow;
use App\Infrastructure\Persistence\Eloquent\Repositories\Automation\AutomationJobRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\Automation\AutomationJobStepRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\Automation\WorkflowRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\Automation\WorkflowStepRepository;
use App\Policies\Automation\AutomationJobPolicy;
use App\Policies\Automation\WorkflowPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class AutomationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(WorkflowRepositoryInterface::class, WorkflowRepository::class);
        $this->app->bind(WorkflowStepRepositoryInterface::class, WorkflowStepRepository::class);
        $this->app->bind(AutomationJobRepositoryInterface::class, AutomationJobRepository::class);
        $this->app->bind(AutomationJobStepRepositoryInterface::class, AutomationJobStepRepository::class);

        $this->app->singleton(ActionRegistry::class, fn ($app) => new ActionRegistry([
            $app->make(SendNotificationAction::class),
            $app->make(LogAuditEventAction::class),
        ]));
    }

    public function boot(): void
    {
        Gate::policy(Workflow::class, WorkflowPolicy::class);
        Gate::policy(AutomationJob::class, AutomationJobPolicy::class);

        // Zero edits to the Ticket/Employee modules' own service
        // providers — this is the only place their events are subscribed
        // to for automation-triggering purposes.
        Event::subscribe(AutomationEventSubscriber::class);
    }
}
