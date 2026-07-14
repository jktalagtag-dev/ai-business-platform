import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { employeeNoteService } from '@/modules/employee/services/employeeNotes';
import type { CreateEmployeeNotePayload, EmployeeNoteResource } from '@/modules/employee/types';
import type { Page } from '@/types/api';

export function useEmployeeNotes(employeeId: string | undefined, cursor?: string) {
  return useQuery<Page<EmployeeNoteResource>>({
    queryKey: ['employee', 'notes', employeeId, cursor],
    queryFn: () => employeeNoteService.list(employeeId as string, cursor),
    enabled: !!employeeId,
  });
}

export function useCreateEmployeeNote(employeeId: string) {
  const queryClient = useQueryClient();
  return useMutation<EmployeeNoteResource, unknown, CreateEmployeeNotePayload>({
    mutationFn: (payload) => employeeNoteService.create(employeeId, payload),
    onSuccess: () =>
      queryClient.invalidateQueries({ queryKey: ['employee', 'notes', employeeId] }),
  });
}
