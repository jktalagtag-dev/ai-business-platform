import { api } from '@/lib/api-client';
import type { CreateTicketCommentPayload, TicketCommentResource } from '@/modules/ticket/types';

/** Create + list only — there is no comment edit/delete endpoint. Internal
 * comments are filtered out server-side for callers who lack the
 * addInternalNote ability — there is no client-side toggle for this. */
export const ticketCommentService = {
  list: (ticketId: string, cursor?: string) =>
    api.getPage<TicketCommentResource>(`/tickets/${ticketId}/comments`, { query: { cursor } }),

  create: (ticketId: string, payload: CreateTicketCommentPayload) =>
    api.post<TicketCommentResource>(`/tickets/${ticketId}/comments`, payload),
};
