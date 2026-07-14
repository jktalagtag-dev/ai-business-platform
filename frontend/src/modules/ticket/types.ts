import type { Resource } from '@/types/api';

export type TicketType =
  | 'hardware'
  | 'software'
  | 'network'
  | 'account_access'
  | 'printer'
  | 'email'
  | 'security'
  | 'other';

export type TicketPriority = 'low' | 'medium' | 'high' | 'critical';

/** Full status universe. `closed` is only reachable via POST /close (not
 * settable through PATCH /status), and both `closed`/`cancelled` are
 * terminal — the backend rejects any further transition once reached. */
export type TicketStatus =
  | 'open'
  | 'assigned'
  | 'in_progress'
  | 'waiting_for_user'
  | 'resolved'
  | 'cancelled'
  | 'closed';

/** The subset PATCH /tickets/{id}/status will accept — `closed` is excluded. */
export type SettableTicketStatus = Exclude<TicketStatus, 'closed'>;

export interface TicketAttributes {
  ticket_number: string;
  employee_id: string;
  assigned_technician_id: string | null;
  department_id: string | null;
  ticket_type: TicketType;
  priority: TicketPriority;
  status: TicketStatus;
  subject: string;
  description: string;
  resolution_notes: string | null;
  resolved_at: string | null;
  closed_at: string | null;
  sla_breached_at: string | null;
  created_at: string;
}

export type TicketResource = Resource<'ticket', TicketAttributes>;

/** `employee_id` is only meaningful for a `tickets.manage` holder creating a
 * ticket on behalf of someone else — omit it to create for yourself. */
export interface CreateTicketPayload {
  employee_id?: string | null;
  type: TicketType;
  priority: TicketPriority;
  subject: string;
  description: string;
}

/** A full replace, not a partial patch — the backend requires all four
 * fields even if only one changed. */
export interface UpdateTicketPayload {
  type: TicketType;
  priority: TicketPriority;
  subject: string;
  description: string;
  resolution_notes?: string | null;
}

export interface AssignTicketPayload {
  technician_employee_id: string;
}

export interface UpdateTicketStatusPayload {
  status: SettableTicketStatus;
  note?: string | null;
}

export interface CloseTicketPayload {
  resolution_notes: string;
}

export interface ReopenTicketPayload {
  reason?: string | null;
}

export type TicketQuickFilter = 'open' | 'resolved' | 'critical' | 'unassigned' | 'my_tickets';

export interface TicketListParams {
  employee_id?: string;
  ticket_number?: string;
  status?: TicketStatus;
  priority?: TicketPriority;
  department_id?: string;
  assigned_technician_id?: string;
  search?: string;
  date_from?: string;
  date_to?: string;
  sort?: 'created_at' | 'priority' | 'status' | 'ticket_number';
  direction?: 'asc' | 'desc';
  per_page?: number;
  cursor?: string;
  quick_filter?: TicketQuickFilter;
}

export interface TicketStatistics {
  open_count: number;
  closed_count: number;
  average_resolution_minutes: number | null;
  by_department: Record<string, number>;
  by_priority: Record<string, number>;
  by_technician: Record<string, number>;
}

// --- Comments ---
// Create + list only — there is no comment edit/delete endpoint.

export interface TicketCommentAttributes {
  ticket_id: string;
  author_employee_id: string | null;
  body: string;
  is_internal: boolean;
  created_at: string;
}

export type TicketCommentResource = Resource<'ticket_comment', TicketCommentAttributes>;

export interface CreateTicketCommentPayload {
  body: string;
  is_internal?: boolean;
}

// --- Attachments ---
// Create + list only — there is no attachment delete endpoint. `url` is an
// unsigned, non-expiring public-disk URL (a known backend gap, not something
// the frontend can fix) — anyone with the link can view the file.

export interface TicketAttachmentAttributes {
  ticket_id: string;
  uploaded_by_employee_id: string | null;
  original_filename: string;
  mime_type: string;
  size_bytes: number;
  url: string;
  created_at: string;
}

export type TicketAttachmentResource = Resource<'ticket_attachment', TicketAttachmentAttributes>;
