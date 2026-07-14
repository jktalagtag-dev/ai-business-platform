import { api } from '@/lib/api-client';
import type {
  AssignTicketPayload,
  CloseTicketPayload,
  CreateTicketPayload,
  ReopenTicketPayload,
  TicketListParams,
  TicketResource,
  TicketStatistics,
  UpdateTicketPayload,
  UpdateTicketStatusPayload,
} from '@/modules/ticket/types';

export const ticketService = {
  list: (params: TicketListParams = {}) => api.getPage<TicketResource>('/tickets', { query: { ...params } }),

  get: (id: string) => api.get<TicketResource>(`/tickets/${id}`),

  create: (payload: CreateTicketPayload) => api.post<TicketResource>('/tickets', payload),

  /** Full replace — always send type/priority/subject/description together. */
  update: (id: string, payload: UpdateTicketPayload) =>
    api.patch<TicketResource>(`/tickets/${id}`, payload),

  assign: (id: string, payload: AssignTicketPayload) =>
    api.post<TicketResource>(`/tickets/${id}/assign`, payload),

  updateStatus: (id: string, payload: UpdateTicketStatusPayload) =>
    api.patch<TicketResource>(`/tickets/${id}/status`, payload),

  close: (id: string, payload: CloseTicketPayload) =>
    api.post<TicketResource>(`/tickets/${id}/close`, payload),

  reopen: (id: string, payload: ReopenTicketPayload) =>
    api.post<TicketResource>(`/tickets/${id}/reopen`, payload),

  statistics: () => api.get<TicketStatistics>('/tickets/statistics'),
};
