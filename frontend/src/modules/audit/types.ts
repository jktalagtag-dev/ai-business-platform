import type { Resource } from '@/types/api';

/**
 * Read-only: there is no create/update/delete endpoint. Every entry is
 * written server-side by `AuditLogService::record()`, called from inside
 * domain services (Employee, Inventory, Ticket, Automation) and from the
 * Automation module's `log_audit_event` action — all sharing this one table,
 * not a per-module audit log.
 */
export interface AuditLogAttributes {
  /** Null for system-triggered entries (a scheduled job outcome, or a
   * workflow's `log_audit_event` action, which always runs with no user
   * actor). */
  actor_user_id: string | null;
  /** Freeform, dot-namespaced (e.g. `employee.created`, `automation.job_failed`).
   * No enum exists server-side, since any workflow's `log_audit_event` action
   * can log an arbitrary action string. */
  action: string;
  /** Also freeform for the same reason — not limited to the ~10 built-in
   * subject types (ticket, employee, product, workflow, ...). */
  subject_type: string;
  subject_id: string;
  /** Arbitrary key/value payload — shape varies per call site (e.g. the
   * changed fields on an update, or `{}` for a lifecycle transition). */
  changes: Record<string, unknown>;
  ip_address: string | null;
  created_at: string;
}

export type AuditLogResource = Resource<'audit_log', AuditLogAttributes>;

export interface AuditLogListParams {
  subject_type?: string;
  subject_id?: string;
  cursor?: string;
  per_page?: number;
}
