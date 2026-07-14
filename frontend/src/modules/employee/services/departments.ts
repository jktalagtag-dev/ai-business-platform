import { api } from '@/lib/api-client';
import type { DepartmentPayload, DepartmentResource } from '@/modules/employee/types';

/** No filters/search/sort exist on this endpoint — hardcoded per_page=25 server-side. */
export const departmentService = {
  list: (params: { cursor?: string } = {}) =>
    api.getPage<DepartmentResource>('/departments', { query: params }),

  get: (id: string) => api.get<DepartmentResource>(`/departments/${id}`),

  create: (payload: DepartmentPayload) => api.post<DepartmentResource>('/departments', payload),

  update: (id: string, payload: DepartmentPayload) =>
    api.patch<DepartmentResource>(`/departments/${id}`, payload),

  remove: (id: string) => api.delete<void>(`/departments/${id}`),
};
