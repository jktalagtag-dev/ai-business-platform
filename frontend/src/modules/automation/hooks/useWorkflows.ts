import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { workflowService } from '@/modules/automation/services/workflows';
import type {
  CreateWorkflowPayload,
  WorkflowListParams,
  WorkflowResource,
  WorkflowStepResource,
} from '@/modules/automation/types';
import type { Page } from '@/types/api';

export function useWorkflows(filter: WorkflowListParams = {}) {
  return useQuery<Page<WorkflowResource>>({
    queryKey: ['automation', 'workflows', filter],
    queryFn: () => workflowService.list(filter),
  });
}

export function useWorkflow(id: string | undefined) {
  return useQuery<WorkflowResource>({
    queryKey: ['automation', 'workflow', id],
    queryFn: () => workflowService.get(id as string),
    enabled: !!id,
  });
}

export function useWorkflowSteps(id: string | undefined) {
  return useQuery<WorkflowStepResource[]>({
    queryKey: ['automation', 'workflow-steps', id],
    queryFn: () => workflowService.steps(id as string),
    enabled: !!id,
  });
}

function useInvalidateWorkflows(id?: string) {
  const queryClient = useQueryClient();
  return () => {
    queryClient.invalidateQueries({ queryKey: ['automation', 'workflows'] });
    if (id) queryClient.invalidateQueries({ queryKey: ['automation', 'workflow', id] });
  };
}

export function useCreateWorkflow() {
  const invalidate = useInvalidateWorkflows();
  return useMutation<WorkflowResource, unknown, CreateWorkflowPayload>({
    mutationFn: (payload) => workflowService.create(payload),
    onSuccess: invalidate,
  });
}

export function useActivateWorkflow(id: string) {
  const invalidate = useInvalidateWorkflows(id);
  return useMutation<WorkflowResource, unknown, void>({
    mutationFn: () => workflowService.activate(id),
    onSuccess: invalidate,
  });
}

export function usePauseWorkflow(id: string) {
  const invalidate = useInvalidateWorkflows(id);
  return useMutation<WorkflowResource, unknown, void>({
    mutationFn: () => workflowService.pause(id),
    onSuccess: invalidate,
  });
}

export function useDeleteWorkflow() {
  const invalidate = useInvalidateWorkflows();
  return useMutation<void, unknown, string>({
    mutationFn: (id) => workflowService.remove(id),
    onSuccess: invalidate,
  });
}
