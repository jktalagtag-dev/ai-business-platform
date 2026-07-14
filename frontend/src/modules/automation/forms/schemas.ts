import { z } from 'zod';
import {
  ACTION_NAMES,
  CONDITION_OPERATORS,
  EVENT_TRIGGER_OPTIONS,
} from '@/modules/automation/types';

/**
 * Zod schemas mirroring StoreWorkflowRequest's rules (client-side UX
 * guardrail only — the server re-validates and `error.details[]` is mapped
 * back onto these same fields via applyApiErrorsToForm). There is no update
 * endpoint, so this is the only form this module has: a workflow is fully
 * specified at creation time or not at all.
 */

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

/** One condition or action step in the builder — a single flat shape (rather
 * than a discriminated union) so react-hook-form's useFieldArray can manage
 * a uniform array; which fields are required is enforced by `superRefine`
 * based on `kind` (and `action`, when `kind === 'action'`). */
export const workflowStepFormSchema = z
  .object({
    kind: z.enum(['condition', 'action']),
    // condition fields
    field: z.string().optional(),
    operator: z.enum(CONDITION_OPERATORS).optional(),
    value: z.string().optional(),
    // action fields
    action: z.enum(ACTION_NAMES).optional(),
    to: z.string().optional(),
    subject: z.string().optional(),
    message: z.string().optional(),
    audit_action: z.string().optional(),
    subject_type: z.string().optional(),
    subject_id: z.string().optional(),
  })
  .superRefine((step, ctx) => {
    if (step.kind === 'condition') {
      if (!step.field) {
        ctx.addIssue({ code: 'custom', path: ['field'], message: 'Field is required' });
      }
      if (!step.operator) {
        ctx.addIssue({ code: 'custom', path: ['operator'], message: 'Operator is required' });
      }
      if (!step.value) {
        ctx.addIssue({ code: 'custom', path: ['value'], message: 'Value is required' });
      }
      return;
    }

    if (!step.action) {
      ctx.addIssue({ code: 'custom', path: ['action'], message: 'Choose an action' });
      return;
    }

    if (step.action === 'send_notification') {
      if (!step.to) {
        ctx.addIssue({ code: 'custom', path: ['to'], message: 'Recipient email is required' });
      } else if (!EMAIL_RE.test(step.to)) {
        ctx.addIssue({ code: 'custom', path: ['to'], message: 'Enter a valid email address' });
      }
      if (!step.subject) {
        ctx.addIssue({ code: 'custom', path: ['subject'], message: 'Subject is required' });
      }
      if (!step.message) {
        ctx.addIssue({ code: 'custom', path: ['message'], message: 'Message is required' });
      }
    } else if (step.action === 'log_audit_event') {
      if (!step.audit_action) {
        ctx.addIssue({ code: 'custom', path: ['audit_action'], message: 'Audit action is required' });
      }
      if (!step.subject_type) {
        ctx.addIssue({ code: 'custom', path: ['subject_type'], message: 'Subject type is required' });
      }
      if (!step.subject_id) {
        ctx.addIssue({ code: 'custom', path: ['subject_id'], message: 'Subject id is required' });
      }
    }
  });
export type WorkflowStepFormValues = z.infer<typeof workflowStepFormSchema>;

export const workflowSchema = z
  .object({
    name: z.string().min(1, 'Name is required').max(255),
    description: z.string().optional().or(z.literal('')),
    trigger_kind: z.enum(['event', 'schedule']),
    trigger_event: z.enum(EVENT_TRIGGER_OPTIONS).optional(),
    trigger_cron: z.string().optional(),
    steps: z.array(workflowStepFormSchema).min(1, 'Add at least one step'),
  })
  .superRefine((values, ctx) => {
    if (values.trigger_kind === 'event' && !values.trigger_event) {
      ctx.addIssue({ code: 'custom', path: ['trigger_event'], message: 'Choose a trigger event' });
    }
    if (values.trigger_kind === 'schedule' && !values.trigger_cron) {
      ctx.addIssue({ code: 'custom', path: ['trigger_cron'], message: 'Enter a cron expression' });
    }
    if (!values.steps.some((step) => step.kind === 'action')) {
      ctx.addIssue({
        code: 'custom',
        path: ['steps'],
        message: 'At least one action step is required',
      });
    }
  });
export type WorkflowFormValues = z.infer<typeof workflowSchema>;
