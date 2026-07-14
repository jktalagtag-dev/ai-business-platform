import { useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { Loader2, Pause, Play, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { ConfirmDialog } from '@/components/ui/confirm-dialog';
import { PageHeader } from '@/components/layout/PageHeader';
import { ErrorState } from '@/components/layout/ErrorState';
import { toast } from '@/components/ui/sonner';
import { isApiError } from '@/lib/errors';
import { useAbility } from '@/hooks/useAbility';
import {
  useActivateWorkflow,
  useDeleteWorkflow,
  usePauseWorkflow,
  useWorkflow,
  useWorkflowSteps,
} from '@/modules/automation/hooks/useWorkflows';
import { WorkflowStatusBadge } from '@/modules/automation/components/WorkflowStatusBadge';
import { WorkflowStepConfigView } from '@/modules/automation/components/WorkflowStepConfigView';
import { AutomationJobsTable } from '@/modules/automation/components/AutomationJobsTable';
import { paths } from '@/routes/routes.config';

export function WorkflowDetailPage() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const canManage = useAbility('automation.manage');

  const { data: workflow, isLoading, isError, refetch } = useWorkflow(id);
  const { data: steps, isLoading: isStepsLoading } = useWorkflowSteps(id);
  const activate = useActivateWorkflow(id ?? '');
  const pause = usePauseWorkflow(id ?? '');
  const deleteWorkflow = useDeleteWorkflow();
  const [deleteOpen, setDeleteOpen] = useState(false);

  if (isLoading) {
    return (
      <div className="flex items-center justify-center rounded-lg border p-12">
        <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (isError || !workflow) {
    return <ErrorState message="Workflow not found." onRetry={() => refetch()} />;
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title={workflow.attributes.name}
        description={workflow.attributes.description ?? undefined}
        actions={
          canManage && (
            <div className="flex gap-2">
              {workflow.attributes.status === 'active' ? (
                <Button
                  variant="outline"
                  disabled={pause.isPending}
                  onClick={() =>
                    pause.mutate(undefined, {
                      onSuccess: () => toast.success('Workflow paused.'),
                      onError: (error) =>
                        toast.error(isApiError(error) ? error.message : 'Unable to pause workflow.'),
                    })
                  }
                >
                  <Pause className="h-4 w-4" />
                  Pause
                </Button>
              ) : (
                <Button
                  variant="outline"
                  disabled={activate.isPending}
                  onClick={() =>
                    activate.mutate(undefined, {
                      onSuccess: () => toast.success('Workflow activated.'),
                      onError: (error) =>
                        toast.error(isApiError(error) ? error.message : 'Unable to activate workflow.'),
                    })
                  }
                >
                  <Play className="h-4 w-4" />
                  Activate
                </Button>
              )}
              <Button variant="destructive" onClick={() => setDeleteOpen(true)}>
                <Trash2 className="h-4 w-4" />
                Delete
              </Button>
            </div>
          )
        }
      />

      <Card>
        <CardContent className="flex flex-wrap items-center gap-3 pt-6 text-sm">
          <WorkflowStatusBadge status={workflow.attributes.status} />
          <span className="text-muted-foreground">
            Last triggered:{' '}
            {workflow.attributes.last_triggered_at
              ? new Date(workflow.attributes.last_triggered_at).toLocaleString()
              : 'Never'}
          </span>
        </CardContent>
      </Card>

      <div>
        <h2 className="mb-3 text-lg font-semibold">Steps</h2>
        {isStepsLoading ? (
          <Loader2 className="h-5 w-5 animate-spin text-muted-foreground" />
        ) : (
          <div className="space-y-2">
            {steps?.map((step) => <WorkflowStepConfigView key={step.id} step={step} />)}
          </div>
        )}
      </div>

      <div>
        <h2 className="mb-3 text-lg font-semibold">Run history</h2>
        <AutomationJobsTable workflowId={workflow.id} />
      </div>

      {canManage && (
        <ConfirmDialog
          open={deleteOpen}
          onOpenChange={setDeleteOpen}
          title="Delete workflow?"
          description="This workflow and its full run history will be permanently removed."
          confirmLabel="Delete"
          isLoading={deleteWorkflow.isPending}
          onConfirm={() => {
            if (!id) return;
            deleteWorkflow.mutate(id, {
              onSuccess: () => {
                toast.success('Workflow deleted.');
                navigate(paths.automation);
              },
              onError: () => toast.error('Unable to delete workflow.'),
            });
          }}
        />
      )}
    </div>
  );
}
