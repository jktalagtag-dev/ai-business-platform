import { Ban, Clock, GitBranch, Play, Send } from 'lucide-react';
import type { WorkflowStepResource } from '@/modules/automation/types';

const OPERATOR_LABEL: Record<string, string> = {
  equals: 'equals',
  not_equals: 'does not equal',
  contains: 'contains',
  greater_than: 'is greater than',
  less_than: 'is less than',
};

const EVENT_LABEL: Record<string, string> = {
  'ticket.created': 'a ticket is created',
  'ticket.assigned': 'a ticket is assigned',
  'ticket.status_changed': "a ticket's status changes",
  'employee.created': 'an employee is created',
  'employee.updated': 'an employee is updated',
  'employee.archived': 'an employee is archived',
};

/** Read-only renderer — there is no edit endpoint, so a workflow's steps are
 * only ever displayed, never edited in place. */
export function WorkflowStepConfigView({ step }: { step: WorkflowStepResource }) {
  const { step_type, config } = step.attributes;

  if (step_type === 'trigger') {
    const kind = config.kind as string | undefined;
    const isSchedule = kind === 'schedule';
    return (
      <div className="flex items-start gap-2 rounded-lg border p-3 text-sm">
        {isSchedule ? (
          <Clock className="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
        ) : (
          <Play className="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
        )}
        <div>
          <div className="font-medium">Trigger</div>
          <div className="text-muted-foreground">
            {isSchedule
              ? `On a schedule — cron "${config.cron as string}"`
              : `When ${EVENT_LABEL[config.event as string] ?? config.event}`}
          </div>
        </div>
      </div>
    );
  }

  if (step_type === 'condition') {
    return (
      <div className="flex items-start gap-2 rounded-lg border p-3 text-sm">
        <GitBranch className="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
        <div>
          <div className="font-medium">Condition</div>
          <div className="text-muted-foreground">
            {String(config.field)} {OPERATOR_LABEL[config.operator as string] ?? String(config.operator)}{' '}
            &ldquo;{String(config.value)}&rdquo;
          </div>
        </div>
      </div>
    );
  }

  // action
  const action = config.action as string | undefined;
  return (
    <div className="flex items-start gap-2 rounded-lg border p-3 text-sm">
      {action === 'send_notification' ? (
        <Send className="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
      ) : (
        <Ban className="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
      )}
      <div>
        <div className="font-medium">Action</div>
        <div className="text-muted-foreground">
          {action === 'send_notification' ? (
            <>
              Send a notification to <span className="font-medium">{String(config.to)}</span>:{' '}
              &ldquo;{String(config.subject)}&rdquo;
            </>
          ) : (
            <>
              Log audit event &ldquo;{String(config.audit_action)}&rdquo; on{' '}
              {String(config.subject_type)}
            </>
          )}
        </div>
      </div>
    </div>
  );
}
