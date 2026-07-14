import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { ticketAttachmentService } from '@/modules/ticket/services/ticketAttachments';
import type { TicketAttachmentResource } from '@/modules/ticket/types';
import type { Page } from '@/types/api';

export function useTicketAttachments(ticketId: string | undefined, cursor?: string) {
  return useQuery<Page<TicketAttachmentResource>>({
    queryKey: ['ticket', 'attachments', ticketId, cursor],
    queryFn: () => ticketAttachmentService.list(ticketId as string, cursor),
    enabled: !!ticketId,
  });
}

export function useUploadTicketAttachment(ticketId: string) {
  const queryClient = useQueryClient();
  return useMutation<TicketAttachmentResource, unknown, File>({
    mutationFn: (file) => ticketAttachmentService.upload(ticketId, file),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['ticket', 'attachments', ticketId] }),
  });
}
