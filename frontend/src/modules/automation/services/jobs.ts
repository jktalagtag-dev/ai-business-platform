import { api } from '@/lib/api-client';
import type {
  AutomationJobListParams,
  AutomationJobResource,
  AutomationJobStepResource,
} from '@/modules/automation/types';

/** Jobs are created only by the backend's own event subscriber or scheduler
 * — there is no manual "run now" endpoint. `retry` only succeeds when the
 * job's current status is `failed`. */
export const automationJobService = {
  list: (params: AutomationJobListParams = {}) =>
    api.getPage<AutomationJobResource>('/automation/jobs', { query: { ...params } }),

  get: (id: string) => api.get<AutomationJobResource>(`/automation/jobs/${id}`),

  steps: (id: string, params: { per_page?: number; cursor?: string } = {}) =>
    api.getPage<AutomationJobStepResource>(`/automation/jobs/${id}/steps`, { query: { ...params } }),

  retry: (id: string) => api.post<AutomationJobResource>(`/automation/jobs/${id}/retry`),
};
