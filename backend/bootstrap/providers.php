<?php

use App\Providers\AiServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\AutomationServiceProvider;
use App\Providers\EmployeeServiceProvider;
use App\Providers\InventoryServiceProvider;
use App\Providers\KnowledgeBaseServiceProvider;
use App\Providers\RbacServiceProvider;
use App\Providers\TenantServiceProvider;
use App\Providers\TicketServiceProvider;

return [
    AppServiceProvider::class,
    RbacServiceProvider::class,
    TenantServiceProvider::class,
    InventoryServiceProvider::class,
    EmployeeServiceProvider::class,
    TicketServiceProvider::class,
    AiServiceProvider::class,
    KnowledgeBaseServiceProvider::class,
    AutomationServiceProvider::class,
];
