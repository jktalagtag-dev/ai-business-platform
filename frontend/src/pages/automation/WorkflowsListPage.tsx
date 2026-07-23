import { useMemo, useState } from 'react';
import type { ColumnDef } from '@tanstack/react-table';
import { useNavigate } from 'react-router-dom';
import { Pause, Play, Plus, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { ConfirmDialog } from '@/components/ui/confirm-dialog';
import { DataTable } from '@/components/data-table/DataTable';
import { PageHeader } from '@/components/layout/PageHeader';
import { toast } from '@/components/ui/sonner';
import { isApiError } from '@/lib/errors';
import { useAbility } from '@/hooks/useAbility';
import {
  useActivateWorkflow,
  useDeleteWorkflow,
  usePauseWorkflow,
  useWorkflows,
} from '@/modules/automation/hooks/useWorkflows';
import { CreateWorkflowDialog } from '@/modules/automation/components/CreateWorkflowDialog';
import { WorkflowStatusBadge } from '@/modules/automation/components/WorkflowStatusBadge';
import { paths } from '@/routes/routes.config';
import type { WorkflowResource } from '@/modules/automation/types';

function ToggleActiveButton({ workflow }: { workflow: WorkflowResource }) {
  const activate = useActivateWorkflow(workflow.id);
  const pause = usePauseWorkflow(workflow.id);

  if (workflow.attributes.status === 'active') {
    return (
      <Button
        variant="ghost"
        size="icon"
        disabled={pause.isPending}
        onClick={(e) => {
          e.stopPropagation();
          pause.mutate(undefined, {
            onSuccess: () => toast.success('Workflow paused.'),
            onError: (error) => toast.error(isApiError(error) ? error.message : 'Unable to pause workflow.'),
          });
        }}
      >
        <Pause className="h-4 w-4" />
        <span className="sr-only">Pause</span>
      </Button>
    );
  }

  return (
    <Button
      variant="ghost"
      size="icon"
      disabled={activate.isPending}
      onClick={(e) => {
        e.stopPropagation();
        activate.mutate(undefined, {
          onSuccess: () => toast.success('Workflow activated.'),
          onError: (error) => toast.error(isApiError(error) ? error.message : 'Unable to activate workflow.'),
        });
      }}
    >
      <Play className="h-4 w-4" />
      <span className="sr-only">Activate</span>
    </Button>
  );
}

export function WorkflowsListPage() {
  const navigate = useNavigate();
  const canManage = useAbility('automation.manage');
  const [cursor, setCursor] = useState<string | undefined>(undefined);
  const { data, isLoading, isError, refetch } = useWorkflows({ cursor });
  const deleteWorkflow = useDeleteWorkflow();

  const [createOpen, setCreateOpen] = useState(false);
  const [deleting, setDeleting] = useState<WorkflowResource | undefined>(undefined);

  const columns = useMemo<ColumnDef<WorkflowResource, unknown>[]>(
    () => [
      { id: 'name', header: 'Name', cell: ({ row }) => row.original.attributes.name },
      {
        id: 'status',
        header: 'Status',
        cell: ({ row }) => <WorkflowStatusBadge status={row.original.attributes.status} />,
      },
      {
        id: 'last_triggered_at',
        header: 'Last triggered',
        cell: ({ row }) =>
          row.original.attributes.last_triggered_at
            ? new Date(row.original.attributes.last_triggered_at).toLocaleString()
            : 'Never',
      },
      {
        id: 'created_at',
        header: 'Created',
        cell: ({ row }) => new Date(row.original.attributes.created_at).toLocaleDateString(),
      },
      ...(canManage
        ? [
            {
              id: 'actions',
              header: '',
              cell: ({ row }: { row: { original: WorkflowResource } }) => (
                <div className="flex justify-end gap-1">
                  <ToggleActiveButton workflow={row.original} />
                  <Button
                    variant="ghost"
                    size="icon"
                    onClick={(e) => {
                      e.stopPropagation();
                      setDeleting(row.original);
                    }}
                  >
                    <Trash2 className="h-4 w-4" />
                    <span className="sr-only">Delete</span>
                  </Button>
                </div>
              ),
            } satisfies ColumnDef<WorkflowResource, unknown>,
          ]
        : []),
    ],
    [canManage]
  );

  return (
    <div>
      <PageHeader
        title="Automation"
        description="Event-driven and scheduled workflows for this workspace."
        actions={
          canManage && (
            <Button onClick={() => setCreateOpen(true)}>
              <Plus className="h-4 w-4" />
              New workflow
            </Button>
          )
        }
      />

      <DataTable
        columns={columns}
        data={data?.items ?? []}
        isLoading={isLoading}
        isError={isError}
        onRetry={() => refetch()}
        emptyTitle="No workflows yet"
        emptyDescription={canManage ? 'Create a workflow to automate a repetitive task.' : undefined}
        onRowClick={(workflow) => navigate(`${paths.automation}/${workflow.id}`)}
        pagination={{
          hasNext: !!data?.pagination.next_cursor,
          hasPrev: !!data?.pagination.prev_cursor,
          onNext: () => setCursor(data?.pagination.next_cursor ?? undefined),
          onPrev: () => setCursor(data?.pagination.prev_cursor ?? undefined),
        }}
      />

      {canManage && (
        <>
          <CreateWorkflowDialog open={createOpen} onOpenChange={setCreateOpen} />
          <ConfirmDialog
            open={!!deleting}
            onOpenChange={(open) => !open && setDeleting(undefined)}
            title="Delete workflow?"
            description={`"${deleting?.attributes.name}" and its full run history will be permanently removed.`}
            confirmLabel="Delete"
            isLoading={deleteWorkflow.isPending}
            onConfirm={() => {
              if (!deleting) return;
              deleteWorkflow.mutate(deleting.id, {
                onSuccess: () => {
                  toast.success('Workflow deleted.');
                  setDeleting(undefined);
                },
                onError: () => toast.error('Unable to delete workflow.'),
              });
            }}
          />
        </>
      )}
    </div>
  );
}
