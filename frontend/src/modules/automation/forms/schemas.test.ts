import { describe, it, expect } from 'vitest';
import { workflowSchema } from '@/modules/automation/forms/schemas';

function baseWorkflow(overrides: Record<string, unknown> = {}) {
  return {
    name: 'Notify ops',
    description: '',
    trigger_kind: 'event',
    trigger_event: 'ticket.created',
    trigger_cron: '',
    steps: [{ kind: 'action', action: 'send_notification', to: 'ops@example.com', subject: 'Hi', message: 'Body' }],
    ...overrides,
  };
}

describe('workflowSchema', () => {
  it('accepts a minimal valid workflow', () => {
    expect(workflowSchema.safeParse(baseWorkflow()).success).toBe(true);
  });

  it('requires a trigger event when trigger_kind is "event"', () => {
    const result = workflowSchema.safeParse(baseWorkflow({ trigger_event: undefined }));
    expect(result.success).toBe(false);
  });

  it('requires a cron expression when trigger_kind is "schedule"', () => {
    const result = workflowSchema.safeParse(
      baseWorkflow({ trigger_kind: 'schedule', trigger_event: undefined, trigger_cron: '' })
    );
    expect(result.success).toBe(false);
  });

  it('accepts a valid schedule trigger', () => {
    const result = workflowSchema.safeParse(
      baseWorkflow({ trigger_kind: 'schedule', trigger_event: undefined, trigger_cron: '0 9 * * 1' })
    );
    expect(result.success).toBe(true);
  });

  it('requires at least one action step', () => {
    const result = workflowSchema.safeParse(
      baseWorkflow({ steps: [{ kind: 'condition', field: 'ticket.priority', operator: 'equals', value: 'critical' }] })
    );
    expect(result.success).toBe(false);
  });

  it('requires field/operator/value on a condition step', () => {
    const result = workflowSchema.safeParse(
      baseWorkflow({
        steps: [
          { kind: 'condition' },
          { kind: 'action', action: 'send_notification', to: 'a@b.com', subject: 'S', message: 'M' },
        ],
      })
    );
    expect(result.success).toBe(false);
  });

  it('requires to/subject/message for a send_notification action', () => {
    const result = workflowSchema.safeParse(baseWorkflow({ steps: [{ kind: 'action', action: 'send_notification' }] }));
    expect(result.success).toBe(false);
  });

  it('rejects an invalid recipient email', () => {
    const result = workflowSchema.safeParse(
      baseWorkflow({
        steps: [{ kind: 'action', action: 'send_notification', to: 'not-an-email', subject: 'S', message: 'M' }],
      })
    );
    expect(result.success).toBe(false);
  });

  it('requires audit_action/subject_type/subject_id for a log_audit_event action', () => {
    const result = workflowSchema.safeParse(baseWorkflow({ steps: [{ kind: 'action', action: 'log_audit_event' }] }));
    expect(result.success).toBe(false);
  });

  it('accepts a valid log_audit_event action', () => {
    const result = workflowSchema.safeParse(
      baseWorkflow({
        steps: [
          {
            kind: 'action',
            action: 'log_audit_event',
            audit_action: 'workflow.escalated',
            subject_type: 'ticket',
            subject_id: '{{ticket.id}}',
          },
        ],
      })
    );
    expect(result.success).toBe(true);
  });
});
