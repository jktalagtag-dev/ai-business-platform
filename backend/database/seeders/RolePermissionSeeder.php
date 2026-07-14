<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\Models\Permission;
use App\Infrastructure\Persistence\Eloquent\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * System roles (tenant_id = null) available to every tenant, and the
     * baseline permission catalogue. Domain-specific permissions are added
     * to this catalogue as each domain is built — inventory.x was added
     * alongside the Inventory module, employees.x, departments.x, and
     * positions.x alongside the Employee Management module (which also
     * introduces the HR role: "Only Admin and HR can create employees");
     * further domains will extend this the same way.
     */
    public function run(): void
    {
        $permissions = collect([
            'profile.view' => 'View own profile',
            'profile.update' => 'Update own profile',
            'members.manage' => 'Invite, remove, and manage tenant members',
            'roles.manage' => 'Assign roles to tenant members',
            'tenant.manage' => 'Manage tenant-level settings',
            'categories.view' => 'View product categories',
            'categories.manage' => 'Create, update, and delete product categories',
            'products.view' => 'View products',
            'products.manage' => 'Create, update, and delete products',
            'suppliers.view' => 'View suppliers',
            'suppliers.manage' => 'Create, update, and delete suppliers',
            'inventory.view' => 'View stock levels and movement history',
            'inventory.manage' => 'Adjust stock quantities',
            'departments.view' => 'View departments',
            'departments.manage' => 'Create, update, and delete departments',
            'positions.view' => 'View job positions',
            'positions.manage' => 'Create, update, and delete job positions',
            'employees.view' => 'View the full employee directory',
            'employees.manage' => 'Create, update, archive employees, and manage employment details',
            'tickets.view' => 'View the full ticket directory across all departments',
            'tickets.manage' => 'Assign/reassign technicians and act on any ticket regardless of ownership',
            'knowledge_base.view' => 'Search/ask the knowledge base and view uploaded documents',
            'knowledge_base.manage' => 'Upload and delete knowledge base documents',
            'automation.view' => 'View workflows and their run/job history',
            'automation.manage' => 'Create, activate, pause, delete workflows, and retry failed jobs',
        ])->map(fn (string $description, string $key) => Permission::firstOrCreate(
            ['key' => $key],
            ['description' => $description]
        ));

        $inventoryViewKeys = ['categories.view', 'products.view', 'suppliers.view', 'inventory.view'];
        $inventoryManageKeys = ['categories.manage', 'products.manage', 'suppliers.manage', 'inventory.manage'];
        $employeeViewKeys = ['departments.view', 'positions.view', 'employees.view'];
        $employeeManageKeys = ['departments.manage', 'positions.manage', 'employees.manage'];

        $roles = [
            'Owner' => $permissions->keys()->all(),
            'Admin' => [
                'profile.view', 'profile.update', 'members.manage', 'roles.manage',
                ...$inventoryViewKeys, ...$inventoryManageKeys,
                ...$employeeViewKeys, ...$employeeManageKeys,
                'tickets.view', 'tickets.manage',
                'knowledge_base.view', 'knowledge_base.manage',
                'automation.view', 'automation.manage',
            ],
            'HR' => [
                'profile.view', 'profile.update',
                ...$employeeViewKeys, ...$employeeManageKeys,
            ],
            'Member' => ['profile.view', 'profile.update', ...$inventoryViewKeys, 'knowledge_base.view'],
        ];

        foreach ($roles as $name => $permissionKeys) {
            $role = Role::firstOrCreate(
                ['tenant_id' => null, 'name' => $name],
                ['is_system' => true]
            );

            $role->permissions()->sync(
                $permissions->only($permissionKeys)->pluck('id')->all()
            );
        }
    }
}
