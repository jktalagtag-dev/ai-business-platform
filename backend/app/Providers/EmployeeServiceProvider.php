<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Contracts\Repositories\Employee\DepartmentRepositoryInterface;
use App\Application\Contracts\Repositories\Employee\EmployeeNoteRepositoryInterface;
use App\Application\Contracts\Repositories\Employee\EmployeeRepositoryInterface;
use App\Application\Contracts\Repositories\Employee\PositionRepositoryInterface;
use App\Application\Contracts\Services\EmployeeCodeGeneratorInterface;
use App\Domain\Employee\Department;
use App\Domain\Employee\Employee;
use App\Domain\Employee\Position;
use App\Infrastructure\Employee\EmployeeCodeGenerator;
use App\Infrastructure\Persistence\Eloquent\Repositories\Employee\DepartmentRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\Employee\EmployeeNoteRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\Employee\EmployeeRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\Employee\PositionRepository;
use App\Policies\Employee\DepartmentPolicy;
use App\Policies\Employee\EmployeePolicy;
use App\Policies\Employee\PositionPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class EmployeeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DepartmentRepositoryInterface::class, DepartmentRepository::class);
        $this->app->bind(PositionRepositoryInterface::class, PositionRepository::class);
        $this->app->bind(EmployeeRepositoryInterface::class, EmployeeRepository::class);
        $this->app->bind(EmployeeNoteRepositoryInterface::class, EmployeeNoteRepository::class);
        $this->app->bind(EmployeeCodeGeneratorInterface::class, EmployeeCodeGenerator::class);
    }

    public function boot(): void
    {
        Gate::policy(Department::class, DepartmentPolicy::class);
        Gate::policy(Position::class, PositionPolicy::class);
        Gate::policy(Employee::class, EmployeePolicy::class);
    }
}
