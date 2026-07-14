import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { ticketService } from '@/modules/ticket/services/tickets';
import type {
  CreateTicketPayload,
  TicketListParams,
  TicketResource,
  UpdateTicketPayload,
} from '@/modules/ticket/types';
import type { Page } from '@/types/api';

export function useTickets(filter: TicketListParams = {}) {
  return useQuery<Page<TicketResource>>({
    queryKey: ['ticket', 'tickets', filter],
    queryFn: () => ticketService.list(filter),
  });
}

export function useTicket(id: string | undefined) {
  return useQuery<TicketResource>({
    queryKey: ['ticket', 'ticket', id],
    queryFn: () => ticketService.get(id as string),
    enabled: !!id,
  });
}

export function useInvalidateTickets(id?: string) {
  const queryClient = useQueryClient();
  return () => {
    queryClient.invalidateQueries({ queryKey: ['ticket', 'tickets'] });
    queryClient.invalidateQueries({ queryKey: ['ticket', 'statistics'] });
    if (id) queryClient.invalidateQueries({ queryKey: ['ticket', 'ticket', id] });
  };
}

export function useCreateTicket() {
  const invalidate = useInvalidateTickets();
  return useMutation<TicketResource, unknown, CreateTicketPayload>({
    mutationFn: (payload) => ticketService.create(payload),
    onSuccess: invalidate,
  });
}

export function useUpdateTicket(id: string) {
  const invalidate = useInvalidateTickets(id);
  return useMutation<TicketResource, unknown, UpdateTicketPayload>({
    mutationFn: (payload) => ticketService.update(id, payload),
    onSuccess: invalidate,
  });
}
