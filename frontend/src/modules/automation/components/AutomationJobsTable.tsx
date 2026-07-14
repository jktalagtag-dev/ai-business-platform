import { useMemo, useState } from 'react';
import type { ColumnDef } from '@tanstack/react-table';
import { ListTree, RotateCcw } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DataTable } from '@/components/data-table/DataTable';
import { toast } from '@/components/ui/sonner';
import { isApiError } from '@/lib/errors';
import { useAbility } from '@/hooks/useAbility';
import { useWorkflows } from '@/modules/automation/hooks/useWorkflows';
import { useAutomationJobs, useRetryAutomationJob } from '@/modules/automation/hooks/useAutomationJobs';
import { AutomationJobStatusBadge } from '@/modules/automation/components/AutomationJobStatusBadge';
import { JobStepsDialog } from '@/modules/automation/components/JobStepsDialog';
import type { AutomationJobResource, AutomationJobStatus } from '@/modules/automation/types';

const ALL_STATUS = '__all__';
const ALL_WORKFLOWS = '__all__';

function formatDate(value: string | null): string {
  return value ? new Date(value).toLocaleString() : '—';
}

/** Reused by both the standalone Jobs page (no `workflowId`, with a workflow
 * picker) and a workflow's own detail page (fixed `workflowId`, no picker). */
export function AutomationJobsTable({ workflowId }: { workflowId?: string }) {
  const canManage = useAbility('automation.manage');
  const { data: workflowsPage } = useWorkflows({ per_page: 100 });

  const [status, setStatus] = useState(ALL_STATUS);
  const [selectedWorkflowId, setSelectedWorkflowId] = useState(ALL_WORKFLOWS);
  const [cursor, setCursor] = useState<string | undefined>(undefined);
  const [viewingSteps, setViewingSteps] = useState<AutomationJobResource | undefined>(undefined);

  const effectiveWorkflowId = workflowId ?? (selectedWorkflowId === ALL_WORKFLOWS ? undefined : selectedWorkflowId);

  const { data, isLoading, isError, refetch } = useAutomationJobs({
    workflow_id: effectiveWorkflowId,
    status: status === ALL_STATUS ? undefined : (status as AutomationJobStatus),
    cursor,
  });
  const retry = useRetryAutomationJob();

  const workflowNameById = useMemo(() => {
    const map = new Map<string, string>();
    for (const wf of workflowsPage?.items ?? []) map.set(wf.id, wf.attributes.name);
    return map;
  }, [workflowsPage]);

  function resetToFirstPage<T>(setter: (v: T) => void) {
    return (v: T) => {
      setCursor(undefined);
      setter(v);
    };
  }

  const columns = useMemo<ColumnDef<AutomationJobResource, unknown>[]>(
    () => [
      ...(workflowId
        ? []
        : [
            {
              id: 'workflow',
              header: 'Workflow',
              cell: ({ row }: { row: { original: AutomationJobResource } }) =>
                workflowNameById.get(row.original.attributes.workflow_id) ?? '—',
            } satisfies ColumnDef<AutomationJobResource, unknown>,
          ]),
      { id: 'trigger', header: 'Trigger', cell: ({ row }) => row.original.attributes.trigger },
      {
        id: 'status',
        header: 'Status',
        cell: ({ row }) => <AutomationJobStatusBadge status={row.original.attributes.status} />,
      },
      {
        id: 'attempts',
        header: 'Attempts',
        cell: ({ row }) => `${row.original.attributes.attempts} / ${row.original.attributes.max_attempts}`,
      },
      {
        id: 'scheduled_at',
        header: 'Scheduled',
        cell: ({ row }) => formatDate(row.original.attributes.scheduled_at),
      },
      {
        id: 'finished_at',
        header: 'Finished',
        cell: ({ row }) => formatDate(row.original.attributes.finished_at),
      },
      {
        id: 'actions',
        header: '',
        cell: ({ row }) => (
          <div className="flex justify-end gap-1">
            <Button variant="ghost" size="icon" onClick={() => setViewingSteps(row.original)}>
              <ListTree className="h-4 w-4" />
              <span className="sr-only">View steps</span>
            </Button>
            {canManage && row.original.attributes.status === 'failed' && (
              <Button
                variant="ghost"
                size="icon"
                disabled={retry.isPending}
                onClick={() =>
                  retry.mutate(row.original.id, {
                    onSuccess: () => toast.success('Job queued for retry.'),
                    onError: (error) =>
                      toast.error(isApiError(error) ? error.message : 'Unable to retry job.'),
                  })
                }
              >
                <RotateCcw className="h-4 w-4" />
                <span className="sr-only">Retry</span>
              </Button>
            )}
          </div>
        ),
      },
    ],
    [workflowId, workflowNameById, canManage, retry]
  );

  return (
    <div>
      <DataTable
        columns={columns}
        data={data?.items ?? []}
        isLoading={isLoading}
        isError={isError}
        onRetry={() => refetch()}
        emptyTitle="No jobs yet"
        toolbar={
          <>
            {!workflowId && (
              <Select value={selectedWorkflowId} onValueChange={resetToFirstPage(setSelectedWorkflowId)}>
                <SelectTrigger className="sm:w-56">
                  <SelectValue placeholder="All workflows" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value={ALL_WORKFLOWS}>All workflows</SelectItem>
                  {(workflowsPage?.items ?? []).map((wf) => (
                    <SelectItem key={wf.id} value={wf.id}>
                      {wf.attributes.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            )}
            <Select value={status} onValueChange={resetToFirstPage(setStatus)}>
              <SelectTrigger className="sm:w-40">
                <SelectValue placeholder="All statuses" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value={ALL_STATUS}>All statuses</SelectItem>
                <SelectItem value="queued">Queued</SelectItem>
                <SelectItem value="running">Running</SelectItem>
                <SelectItem value="succeeded">Succeeded</SelectItem>
                <SelectItem value="failed">Failed</SelectItem>
              </SelectContent>
            </Select>
          </>
        }
        pagination={{
          hasNext: !!data?.pagination.next_cursor,
          hasPrev: !!data?.pagination.prev_cursor,
          onNext: () => setCursor(data?.pagination.next_cursor ?? undefined),
          onPrev: () => setCursor(data?.pagination.prev_cursor ?? undefined),
        }}
      />

      <JobStepsDialog
        open={!!viewingSteps}
        onOpenChange={(open) => !open && setViewingSteps(undefined)}
        job={viewingSteps}
      />
    </div>
  );
}
