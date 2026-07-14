import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { ticketCommentService } from '@/modules/ticket/services/ticketComments';
import type { CreateTicketCommentPayload, TicketCommentResource } from '@/modules/ticket/types';
import type { Page } from '@/types/api';

export function useTicketComments(ticketId: string | undefined, cursor?: string) {
  return useQuery<Page<TicketCommentResource>>({
    queryKey: ['ticket', 'comments', ticketId, cursor],
    queryFn: () => ticketCommentService.list(ticketId as string, cursor),
    enabled: !!ticketId,
  });
}

export function useCreateTicketComment(ticketId: string) {
  const queryClient = useQueryClient();
  return useMutation<TicketCommentResource, unknown, CreateTicketCommentPayload>({
    mutationFn: (payload) => ticketCommentService.create(ticketId, payload),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['ticket', 'comments', ticketId] }),
  });
}
