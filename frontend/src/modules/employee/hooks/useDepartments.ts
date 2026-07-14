import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { departmentService } from '@/modules/employee/services/departments';
import type { DepartmentPayload, DepartmentResource } from '@/modules/employee/types';
import type { Page } from '@/types/api';

export function useDepartments(cursor?: string) {
  return useQuery<Page<DepartmentResource>>({
    queryKey: ['employee', 'departments', { cursor }],
    queryFn: () => departmentService.list({ cursor }),
  });
}

export function useDepartment(id: string | undefined) {
  return useQuery<DepartmentResource>({
    queryKey: ['employee', 'department', id],
    queryFn: () => departmentService.get(id as string),
    enabled: !!id,
  });
}

function useInvalidateDepartments() {
  const queryClient = useQueryClient();
  return () => queryClient.invalidateQueries({ queryKey: ['employee', 'departments'] });
}

export function useCreateDepartment() {
  const invalidate = useInvalidateDepartments();
  return useMutation<DepartmentResource, unknown, DepartmentPayload>({
    mutationFn: (payload) => departmentService.create(payload),
    onSuccess: invalidate,
  });
}

export function useUpdateDepartment(id: string) {
  const invalidate = useInvalidateDepartments();
  return useMutation<DepartmentResource, unknown, DepartmentPayload>({
    mutationFn: (payload) => departmentService.update(id, payload),
    onSuccess: invalidate,
  });
}

export function useDeleteDepartment() {
  const invalidate = useInvalidateDepartments();
  return useMutation<void, unknown, string>({
    mutationFn: (id) => departmentService.remove(id),
    onSuccess: invalidate,
  });
}
