import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { employeeService } from '@/modules/employee/services/employees';
import type {
  CreateEmployeePayload,
  EmployeeListParams,
  EmployeeResource,
  UpdateEmployeePayload,
} from '@/modules/employee/types';
import type { Page } from '@/types/api';

export function useEmployees(filter: EmployeeListParams = {}) {
  return useQuery<Page<EmployeeResource>>({
    queryKey: ['employee', 'employees', filter],
    queryFn: () => employeeService.list(filter),
  });
}

export function useEmployee(id: string | undefined) {
  return useQuery<EmployeeResource>({
    queryKey: ['employee', 'employee', id],
    queryFn: () => employeeService.get(id as string),
    enabled: !!id,
  });
}

/** The current user's own employee record, if linked to one. A 404 is a
 * normal outcome (not every user account has a linked employee), so callers
 * should check `isError`/`error` rather than treat it as unexpected. */
export function useMyEmployeeProfile() {
  return useQuery<EmployeeResource>({
    queryKey: ['employee', 'me'],
    queryFn: () => employeeService.me(),
    retry: false,
  });
}

function useInvalidateEmployees(id?: string) {
  const queryClient = useQueryClient();
  return () => {
    queryClient.invalidateQueries({ queryKey: ['employee', 'employees'] });
    queryClient.invalidateQueries({ queryKey: ['employee', 'me'] });
    if (id) queryClient.invalidateQueries({ queryKey: ['employee', 'employee', id] });
  };
}

export function useCreateEmployee() {
  const invalidate = useInvalidateEmployees();
  return useMutation<EmployeeResource, unknown, CreateEmployeePayload>({
    mutationFn: (payload) => employeeService.create(payload),
    onSuccess: invalidate,
  });
}

export function useUpdateEmployee(id: string) {
  const invalidate = useInvalidateEmployees(id);
  return useMutation<EmployeeResource, unknown, UpdateEmployeePayload>({
    mutationFn: (payload) => employeeService.update(id, payload),
    onSuccess: invalidate,
  });
}

export function useDeleteEmployee() {
  const invalidate = useInvalidateEmployees();
  return useMutation<void, unknown, string>({
    mutationFn: (id) => employeeService.remove(id),
    onSuccess: invalidate,
  });
}

export function useUploadEmployeeAvatar(id: string) {
  const invalidate = useInvalidateEmployees(id);
  return useMutation<EmployeeResource, unknown, File>({
    mutationFn: (file) => employeeService.uploadAvatar(id, file),
    onSuccess: invalidate,
  });
}
