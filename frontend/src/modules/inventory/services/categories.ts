import { api } from '@/lib/api-client';
import type { CategoryPayload, CategoryResource } from '@/modules/inventory/types';

/** No filters/search exist on this endpoint — the backend returns all
 * categories for the tenant ordered by name. */
export const categoryService = {
  list: (params: { cursor?: string } = {}) =>
    api.getPage<CategoryResource>('/categories', { query: params }),

  get: (id: string) => api.get<CategoryResource>(`/categories/${id}`),

  create: (payload: CategoryPayload) => api.post<CategoryResource>('/categories', payload),

  update: (id: string, payload: CategoryPayload) =>
    api.patch<CategoryResource>(`/categories/${id}`, payload),

  remove: (id: string) => api.delete<void>(`/categories/${id}`),
};
