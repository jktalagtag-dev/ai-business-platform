import { api } from '@/lib/api-client';
import type {
  CreateWorkflowPayload,
  WorkflowListParams,
  WorkflowResource,
  WorkflowStepResource,
} from '@/modules/automation/types';

/** No update endpoint exists — a workflow's steps are immutable once
 * created. The only lifecycle transitions are activate/pause/delete. */
export const workflowService = {
  list: (params: WorkflowListParams = {}) =>
    api.getPage<WorkflowResource>('/automation/workflows', { query: { ...params } }),

  get: (id: string) => api.get<WorkflowResource>(`/automation/workflows/${id}`),

  /** A plain (non-paginated) array, unlike every other list endpoint in this API. */
  steps: (id: string) => api.get<WorkflowStepResource[]>(`/automation/workflows/${id}/steps`),

  create: (payload: CreateWorkflowPayload) =>
    api.post<WorkflowResource>('/automation/workflows', payload),

  activate: (id: string) => api.post<WorkflowResource>(`/automation/workflows/${id}/activate`),

  pause: (id: string) => api.post<WorkflowResource>(`/automation/workflows/${id}/pause`),

  remove: (id: string) => api.delete<void>(`/automation/workflows/${id}`),
};
