import type { WorkflowFormValues } from '@/modules/automation/forms/schemas';
import type { CreateWorkflowPayload, WorkflowStepInput } from '@/modules/automation/types';

/** Assembles the POST payload from form values — step 0 is always the
 * trigger (implicit from array position, per the backend's rule), followed
 * by the user-ordered condition/action steps. */
export function buildWorkflowPayload(values: WorkflowFormValues): CreateWorkflowPayload {
  const steps: WorkflowStepInput[] = [
    {
      type: 'trigger',
      config:
        values.trigger_kind === 'event'
          ? { kind: 'event', event: values.trigger_event }
          : { kind: 'schedule', cron: values.trigger_cron ?? '' },
    },
  ];

  for (const step of values.steps) {
    if (step.kind === 'condition') {
      steps.push({
        type: 'condition',
        config: { field: step.field ?? '', operator: step.operator ?? 'equals', value: step.value ?? '' },
      });
    } else if (step.action === 'send_notification') {
      steps.push({
        type: 'action',
        config: {
          action: 'send_notification',
          to: step.to ?? '',
          subject: step.subject ?? '',
          message: step.message ?? '',
        },
      });
    } else {
      steps.push({
        type: 'action',
        config: {
          action: 'log_audit_event',
          audit_action: step.audit_action ?? '',
          subject_type: step.subject_type ?? '',
          subject_id: step.subject_id ?? '',
        },
      });
    }
  }

  return {
    name: values.name,
    description: values.description || null,
    steps,
  };
}
