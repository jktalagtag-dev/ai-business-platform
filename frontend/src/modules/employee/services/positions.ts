import { api } from '@/lib/api-client';
import type { PositionPayload, PositionResource } from '@/modules/employee/types';

/** No filters/search/sort exist on this endpoint — hardcoded per_page=25 server-side. */
export const positionService = {
  list: (params: { cursor?: string } = {}) =>
    api.getPage<PositionResource>('/positions', { query: params }),

  get: (id: string) => api.get<PositionResource>(`/positions/${id}`),

  create: (payload: PositionPayload) => api.post<PositionResource>('/positions', payload),

  update: (id: string, payload: PositionPayload) =>
    api.patch<PositionResource>(`/positions/${id}`, payload),

  remove: (id: string) => api.delete<void>(`/positions/${id}`),
};
