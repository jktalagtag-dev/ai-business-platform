import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { DataTable } from '@/components/data-table/DataTable';
import { AutomationJobStepStatusBadge } from '@/modules/automation/components/AutomationJobStepStatusBadge';
import { useAutomationJobSteps } from '@/modules/automation/hooks/useAutomationJobs';
import type { AutomationJobResource, AutomationJobStepResource } from '@/modules/automation/types';
import type { ColumnDef } from '@tanstack/react-table';
import { useMemo } from 'react';

function formatJson(value: Record<string, unknown> | null): string | null {
  if (!value) return null;
  return JSON.stringify(value, null, 2);
}

export function JobStepsDialog({
  open,
  onOpenChange,
  job,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  job?: AutomationJobResource;
}) {
  const { data, isLoading, isError, refetch } = useAutomationJobSteps(open ? job?.id : undefined);

  const columns = useMemo<ColumnDef<AutomationJobStepResource, unknown>[]>(
    () => [
      { id: 'step_order', header: '#', cell: ({ row }) => row.original.attributes.step_order },
      {
        id: 'step_type',
        header: 'Type',
        cell: ({ row }) => row.original.attributes.step_type,
      },
      {
        id: 'status',
        header: 'Status',
        cell: ({ row }) => <AutomationJobStepStatusBadge status={row.original.attributes.status} />,
      },
      {
        id: 'detail',
        header: 'Output / error',
        cell: ({ row }) => {
          const { error, output } = row.original.attributes;
          if (error) return <span className="text-destructive">{error}</span>;
          const formatted = formatJson(output);
          if (!formatted) return <span className="text-muted-foreground">—</span>;
          return (
            <details>
              <summary className="cursor-pointer text-xs text-muted-foreground">View output</summary>
              <pre className="mt-1 max-w-md overflow-x-auto rounded bg-muted p-2 text-xs">{formatted}</pre>
            </details>
          );
        },
      },
    ],
    []
  );

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-2xl">
        <DialogHeader>
          <DialogTitle>Job steps</DialogTitle>
          {job && <DialogDescription>Trigger: {job.attributes.trigger}</DialogDescription>}
        </DialogHeader>

        <DataTable
          columns={columns}
          data={data?.items ?? []}
          isLoading={isLoading}
          isError={isError}
          onRetry={() => refetch()}
          emptyTitle="No steps recorded"
        />
      </DialogContent>
    </Dialog>
  );
}
