import { api } from '@/lib/api-client';
import type {
  CreateEmployeePayload,
  EmployeeListParams,
  EmployeeResource,
  UpdateEmployeePayload,
} from '@/modules/employee/types';

export const employeeService = {
  list: (params: EmployeeListParams = {}) =>
    api.getPage<EmployeeResource>('/employees', { query: { ...params } }),

  get: (id: string) => api.get<EmployeeResource>(`/employees/${id}`),

  /** The authenticated user's own employee record — 404 if not linked to one. */
  me: () => api.get<EmployeeResource>('/employees/me'),

  create: (payload: CreateEmployeePayload) => api.post<EmployeeResource>('/employees', payload),

  /** Always send the full current record, not just changed fields — the
   * backend rejects a self-service update (an employee editing their own
   * record without `employees.manage`) if any restricted field
   * (department/position/manager/employment type or status/hire or
   * termination date) differs from its stored value, even by omission. */
  update: (id: string, payload: UpdateEmployeePayload) =>
    api.patch<EmployeeResource>(`/employees/${id}`, payload),

  /** Soft-delete / archive. */
  remove: (id: string) => api.delete<void>(`/employees/${id}`),

  uploadAvatar: (id: string, file: File) => {
    const form = new FormData();
    form.append('avatar', file);
    return api.postForm<EmployeeResource>(`/employees/${id}/avatar`, form);
  },
};
