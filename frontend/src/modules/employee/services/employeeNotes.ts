import { api } from '@/lib/api-client';
import type { CreateEmployeeNotePayload, EmployeeNoteResource } from '@/modules/employee/types';

/** Create + list only — there is no show/update/delete endpoint for notes. */
export const employeeNoteService = {
  list: (employeeId: string, cursor?: string) =>
    api.getPage<EmployeeNoteResource>(`/employees/${employeeId}/notes`, { query: { cursor } }),

  create: (employeeId: string, payload: CreateEmployeeNotePayload) =>
    api.post<EmployeeNoteResource>(`/employees/${employeeId}/notes`, payload),
};
