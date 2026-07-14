import { useMutation } from '@tanstack/react-query';
import { ticketService } from '@/modules/ticket/services/tickets';
import { useInvalidateTickets } from '@/modules/ticket/hooks/useTickets';
import type {
  AssignTicketPayload,
  CloseTicketPayload,
  ReopenTicketPayload,
  TicketResource,
  UpdateTicketStatusPayload,
} from '@/modules/ticket/types';

export function useAssignTicket(id: string) {
  const invalidate = useInvalidateTickets(id);
  return useMutation<TicketResource, unknown, AssignTicketPayload>({
    mutationFn: (payload) => ticketService.assign(id, payload),
    onSuccess: invalidate,
  });
}

export function useUpdateTicketStatus(id: string) {
  const invalidate = useInvalidateTickets(id);
  return useMutation<TicketResource, unknown, UpdateTicketStatusPayload>({
    mutationFn: (payload) => ticketService.updateStatus(id, payload),
    onSuccess: invalidate,
  });
}

export function useCloseTicket(id: string) {
  const invalidate = useInvalidateTickets(id);
  return useMutation<TicketResource, unknown, CloseTicketPayload>({
    mutationFn: (payload) => ticketService.close(id, payload),
    onSuccess: invalidate,
  });
}

export function useReopenTicket(id: string) {
  const invalidate = useInvalidateTickets(id);
  return useMutation<TicketResource, unknown, ReopenTicketPayload>({
    mutationFn: (payload) => ticketService.reopen(id, payload),
    onSuccess: invalidate,
  });
}
