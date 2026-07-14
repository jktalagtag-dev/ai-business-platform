import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { positionService } from '@/modules/employee/services/positions';
import type { PositionPayload, PositionResource } from '@/modules/employee/types';
import type { Page } from '@/types/api';

export function usePositions(cursor?: string) {
  return useQuery<Page<PositionResource>>({
    queryKey: ['employee', 'positions', { cursor }],
    queryFn: () => positionService.list({ cursor }),
  });
}

export function usePosition(id: string | undefined) {
  return useQuery<PositionResource>({
    queryKey: ['employee', 'position', id],
    queryFn: () => positionService.get(id as string),
    enabled: !!id,
  });
}

function useInvalidatePositions() {
  const queryClient = useQueryClient();
  return () => queryClient.invalidateQueries({ queryKey: ['employee', 'positions'] });
}

export function useCreatePosition() {
  const invalidate = useInvalidatePositions();
  return useMutation<PositionResource, unknown, PositionPayload>({
    mutationFn: (payload) => positionService.create(payload),
    onSuccess: invalidate,
  });
}

export function useUpdatePosition(id: string) {
  const invalidate = useInvalidatePositions();
  return useMutation<PositionResource, unknown, PositionPayload>({
    mutationFn: (payload) => positionService.update(id, payload),
    onSuccess: invalidate,
  });
}

export function useDeletePosition() {
  const invalidate = useInvalidatePositions();
  return useMutation<void, unknown, string>({
    mutationFn: (id) => positionService.remove(id),
    onSuccess: invalidate,
  });
}
