<?php

declare(strict_types=1);

namespace App\Application\Contracts\Services;

interface TicketCodeGeneratorInterface
{
    /**
     * Atomically issues the next system-generated ticket number for a
     * tenant, e.g. "TCK-000123". Never user-supplied.
     */
    public function next(string $tenantId): string;
}
