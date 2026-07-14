import { useQuery } from '@tanstack/react-query';
import { ticketService } from '@/modules/ticket/services/tickets';
import type { TicketStatistics } from '@/modules/ticket/types';

/** Scoped identically to the ticket list — full totals for `tickets.view`
 * holders, otherwise scoped to the caller's own requester/assignee/managed
 * department tickets. No filters accepted by this endpoint. */
export function useTicketStatistics() {
  return useQuery<TicketStatistics>({
    queryKey: ['ticket', 'statistics'],
    queryFn: () => ticketService.statistics(),
  });
}
