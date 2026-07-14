import { api } from '@/lib/api-client';
import type { TicketAttachmentResource } from '@/modules/ticket/types';

/** Create + list only — there is no attachment delete endpoint. */
export const ticketAttachmentService = {
  list: (ticketId: string, cursor?: string) =>
    api.getPage<TicketAttachmentResource>(`/tickets/${ticketId}/attachments`, { query: { cursor } }),

  upload: (ticketId: string, file: File) => {
    const form = new FormData();
    form.append('file', file);
    return api.postForm<TicketAttachmentResource>(`/tickets/${ticketId}/attachments`, form);
  },
};
