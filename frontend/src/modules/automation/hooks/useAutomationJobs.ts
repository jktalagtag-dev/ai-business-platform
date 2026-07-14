import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { automationJobService } from '@/modules/automation/services/jobs';
import type { AutomationJobListParams, AutomationJobResource } from '@/modules/automation/types';
import type { Page } from '@/types/api';

const POLL_INTERVAL_MS = 3000;

/** Exported standalone so the polling decision can be unit-tested without
 * mounting a real query — same pattern as Knowledge Base's getKbPollInterval. */
export function getAutomationJobPollInterval(
  data: Page<AutomationJobResource> | undefined
): number | false {
  const hasPending = data?.items.some(
    (job) => job.attributes.status === 'queued' || job.attributes.status === 'running'
  );
  return hasPending ? POLL_INTERVAL_MS : false;
}

/** Jobs run asynchronously with no push notification on completion, so this
 * polls while any job in the current page is still queued/running. */
export function useAutomationJobs(filter: AutomationJobListParams = {}) {
  return useQuery<Page<AutomationJobResource>>({
    queryKey: ['automation', 'jobs', filter],
    queryFn: () => automationJobService.list(filter),
    refetchInterval: (query) =>
      getAutomationJobPollInterval(query.state.data as Page<AutomationJobResource> | undefined),
  });
}

export function useAutomationJob(id: string | undefined) {
  return useQuery<AutomationJobResource>({
    queryKey: ['automation', 'job', id],
    queryFn: () => automationJobService.get(id as string),
    enabled: !!id,
    refetchInterval: (query) => {
      const job = query.state.data as AutomationJobResource | undefined;
      return job && (job.attributes.status === 'queued' || job.attributes.status === 'running')
        ? POLL_INTERVAL_MS
        : false;
    },
  });
}

export function useAutomationJobSteps(id: string | undefined, cursor?: string) {
  return useQuery({
    queryKey: ['automation', 'job-steps', id, cursor],
    queryFn: () => automationJobService.steps(id as string, { cursor, per_page: 200 }),
    enabled: !!id,
  });
}

export function useRetryAutomationJob() {
  const queryClient = useQueryClient();
  return useMutation<AutomationJobResource, unknown, string>({
    mutationFn: (id) => automationJobService.retry(id),
    onSuccess: (_, id) => {
      queryClient.invalidateQueries({ queryKey: ['automation', 'jobs'] });
      queryClient.invalidateQueries({ queryKey: ['automation', 'job', id] });
      queryClient.invalidateQueries({ queryKey: ['automation', 'job-steps', id] });
    },
  });
}
