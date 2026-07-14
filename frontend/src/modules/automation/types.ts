import type { Resource } from '@/types/api';

/** `draft` (on create) → `active` (via /activate) ↔ `paused` (via /pause).
 * There is no rename/edit endpoint — a workflow's steps are immutable once
 * created; the only lifecycle transitions are activate/pause/delete. */
export type WorkflowStatus = 'draft' | 'active' | 'paused';

export type WorkflowStepType = 'trigger' | 'condition' | 'action';

export type TriggerKind = 'event' | 'schedule';

/** Hardcoded in the backend (StoreWorkflowRequest::EVENT_TRIGGERS) — there is
 * no endpoint listing valid events, so this list must be kept in sync by hand. */
export const EVENT_TRIGGER_OPTIONS = [
  'ticket.created',
  'ticket.assigned',
  'ticket.status_changed',
  'employee.created',
  'employee.updated',
  'employee.archived',
] as const;
export type EventTriggerKey = (typeof EVENT_TRIGGER_OPTIONS)[number];

/** ConditionEvaluator::OPERATORS, hardcoded backend-side. */
export const CONDITION_OPERATORS = [
  'equals',
  'not_equals',
  'contains',
  'greater_than',
  'less_than',
] as const;
export type ConditionOperator = (typeof CONDITION_OPERATORS)[number];

/** The only two actions ActionRegistry has registered — hardcoded, since no
 * endpoint exposes the registry's contents or each action's parameter schema. */
export const ACTION_NAMES = ['send_notification', 'log_audit_event'] as const;
export type ActionName = (typeof ACTION_NAMES)[number];

export interface TriggerStepConfig {
  kind: TriggerKind;
  event?: EventTriggerKey;
  cron?: string;
}

export interface ConditionStepConfig {
  field: string;
  operator: ConditionOperator;
  value: string;
}

export interface SendNotificationConfig {
  action: 'send_notification';
  to: string;
  subject: string;
  message: string;
}

export interface LogAuditEventConfig {
  action: 'log_audit_event';
  audit_action: string;
  subject_type: string;
  subject_id: string;
}

export type ActionStepConfig = SendNotificationConfig | LogAuditEventConfig;

/** What gets POSTed for one step — `config`'s shape depends on `type`. */
export interface WorkflowStepInput {
  type: WorkflowStepType;
  config: TriggerStepConfig | ConditionStepConfig | ActionStepConfig;
}

export interface CreateWorkflowPayload {
  name: string;
  description?: string | null;
  /** Step 0 must be the trigger; at least one action step is required. Order
   * is implicit from array position — there's no explicit step_order input. */
  steps: WorkflowStepInput[];
}

export interface WorkflowAttributes {
  name: string;
  description: string | null;
  status: WorkflowStatus;
  created_by_user_id: string;
  last_triggered_at: string | null;
  created_at: string;
  updated_at: string;
}

export type WorkflowResource = Resource<'workflow', WorkflowAttributes>;

export interface WorkflowStepAttributes {
  step_order: number;
  step_type: WorkflowStepType;
  /** Shape varies with step_type — see TriggerStepConfig/ConditionStepConfig/ActionStepConfig. */
  config: Record<string, unknown>;
}

export type WorkflowStepResource = Resource<'workflow_step', WorkflowStepAttributes>;

export interface WorkflowListParams {
  per_page?: number;
  cursor?: string;
}

export type AutomationJobStatus = 'queued' | 'running' | 'succeeded' | 'failed';

export interface AutomationJobAttributes {
  workflow_id: string;
  /** The firing event key, or the literal string "schedule". */
  trigger: string;
  status: AutomationJobStatus;
  attempts: number;
  max_attempts: number;
  context: Record<string, unknown>;
  error: string | null;
  scheduled_at: string;
  started_at: string | null;
  finished_at: string | null;
  created_at: string;
  updated_at: string;
}

export type AutomationJobResource = Resource<'automation_job', AutomationJobAttributes>;

export interface AutomationJobListParams {
  workflow_id?: string;
  status?: AutomationJobStatus;
  per_page?: number;
  cursor?: string;
}

export type AutomationJobStepStatus = 'pending' | 'running' | 'succeeded' | 'failed' | 'skipped';

export interface AutomationJobStepAttributes {
  workflow_step_id: string | null;
  step_order: number;
  step_type: WorkflowStepType;
  status: AutomationJobStepStatus;
  output: Record<string, unknown> | null;
  error: string | null;
  started_at: string | null;
  finished_at: string | null;
  created_at: string;
}

export type AutomationJobStepResource = Resource<'automation_job_step', AutomationJobStepAttributes>;
