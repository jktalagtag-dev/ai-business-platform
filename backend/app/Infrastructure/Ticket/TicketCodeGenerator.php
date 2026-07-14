<?php

declare(strict_types=1);

namespace App\Infrastructure\Ticket;

use App\Application\Contracts\Services\TicketCodeGeneratorInterface;
use Illuminate\Support\Facades\DB;

final class TicketCodeGenerator implements TicketCodeGeneratorInterface
{
    public function next(string $tenantId): string
    {
        $number = DB::transaction(function () use ($tenantId): int {
            DB::table('ticket_id_sequences')->insertOrIgnore(['tenant_id' => $tenantId, 'next_number' => 1]);

            $current = DB::table('ticket_id_sequences')
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->value('next_number');

            DB::table('ticket_id_sequences')
                ->where('tenant_id', $tenantId)
                ->update(['next_number' => $current + 1]);

            return $current;
        });

        return sprintf('TCK-%06d', $number);
    }
}
