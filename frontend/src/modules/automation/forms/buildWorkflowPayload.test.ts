import { describe, it, expect } from 'vitest';
import { buildWorkflowPayload } from '@/modules/automation/forms/buildWorkflowPayload';
import { workflowSchema } from '@/modules/automation/forms/schemas';

describe('buildWorkflowPayload', () => {
  it('puts the trigger first, followed by the user-ordered steps', () => {
    const values = workflowSchema.parse({
      name: 'Notify ops',
      description: '',
      trigger_kind: 'event',
      trigger_event: 'ticket.created',
      trigger_cron: '',
      steps: [
        { kind: 'condition', field: 'ticket.priority', operator: 'equals', value: 'critical' },
        { kind: 'action', action: 'send_notification', to: 'ops@example.com', subject: 'Hi', message: 'Body' },
      ],
    });

    const payload = buildWorkflowPayload(values);

    expect(payload.steps).toHaveLength(3);
    expect(payload.steps[0]).toEqual({ type: 'trigger', config: { kind: 'event', event: 'ticket.created' } });
    expect(payload.steps[1]).toEqual({
      type: 'condition',
      config: { field: 'ticket.priority', operator: 'equals', value: 'critical' },
    });
    expect(payload.steps[2]).toEqual({
      type: 'action',
      config: { action: 'send_notification', to: 'ops@example.com', subject: 'Hi', message: 'Body' },
    });
  });

  it('builds a schedule trigger config from the cron field', () => {
    const values = workflowSchema.parse({
      name: 'Weekly digest',
      description: '',
      trigger_kind: 'schedule',
      trigger_cron: '0 9 * * 1',
      steps: [
        {
          kind: 'action',
          action: 'log_audit_event',
          audit_action: 'workflow.ran',
          subject_type: 'workflow',
          subject_id: 'self',
        },
      ],
    });

    const payload = buildWorkflowPayload(values);
    expect(payload.steps[0]).toEqual({ type: 'trigger', config: { kind: 'schedule', cron: '0 9 * * 1' } });
  });

  it('omits an empty description as null', () => {
    const values = workflowSchema.parse({
      name: 'Notify ops',
      description: '',
      trigger_kind: 'event',
      trigger_event: 'ticket.created',
      steps: [{ kind: 'action', action: 'send_notification', to: 'a@b.com', subject: 'S', message: 'M' }],
    });

    expect(buildWorkflowPayload(values).description).toBeNull();
  });
});
