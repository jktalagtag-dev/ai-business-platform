import { z } from 'zod';
import { NONE_OPTION } from '@/modules/employee/forms/schemas';

/**
 * Zod schemas mirroring the backend Form Requests for Ticketing (client-side
 * UX guardrail only — the server re-validates and `error.details[]` is mapped
 * back onto these same fields via applyApiErrorsToForm).
 */

export { NONE_OPTION };

export const ticketTypeOptions = [
  'hardware',
  'software',
  'network',
  'account_access',
  'printer',
  'email',
  'security',
  'other',
] as const;

export const ticketPriorityOptions = ['low', 'medium', 'high', 'critical'] as const;

/** Settable via PATCH /tickets/{id}/status — `closed` is deliberately
 * excluded (only reachable via POST /close). */
export const settableTicketStatusOptions = [
  'open',
  'assigned',
  'in_progress',
  'waiting_for_user',
  'resolved',
  'cancelled',
] as const;

export const ticketSchema = z.object({
  // Only rendered/sent for a tickets.manage holder creating on behalf of
  // someone else — omitted (sentinel) means "create for myself".
  employee_id: z.string().default(NONE_OPTION),
  type: z.enum(ticketTypeOptions),
  priority: z.enum(ticketPriorityOptions),
  subject: z.string().min(1, 'Subject is required').max(255),
  description: z.string().min(1, 'Description is required'),
  // Only present/editable on the update form, never on create.
  resolution_notes: z.string().optional().or(z.literal('')),
});
export type TicketFormValues = z.infer<typeof ticketSchema>;

export const assignTicketSchema = z.object({
  // EmployeeSelect always renders a "none" sentinel option (labeled via its
  // `placeholder` prop) even when the field itself is required — reject it
  // explicitly so picking that option surfaces a clean client-side message
  // instead of a confusing 422 from the backend's exists-in-tenant rule.
  technician_employee_id: z
    .string()
    .min(1, 'Choose a technician')
    .refine((v) => v !== NONE_OPTION, 'Choose a technician'),
});
export type AssignTicketFormValues = z.infer<typeof assignTicketSchema>;

export const updateTicketStatusSchema = z.object({
  status: z.enum(settableTicketStatusOptions),
  note: z.string().max(1000).optional().or(z.literal('')),
});
export type UpdateTicketStatusFormValues = z.infer<typeof updateTicketStatusSchema>;

export const closeTicketSchema = z.object({
  resolution_notes: z.string().min(1, 'Resolution notes are required'),
});
export type CloseTicketFormValues = z.infer<typeof closeTicketSchema>;

export const reopenTicketSchema = z.object({
  reason: z.string().max(1000).optional().or(z.literal('')),
});
export type ReopenTicketFormValues = z.infer<typeof reopenTicketSchema>;

export const ticketCommentSchema = z.object({
  body: z.string().min(1, 'Comment cannot be empty').max(5000),
  is_internal: z.boolean().default(false),
});
export type TicketCommentFormValues = z.infer<typeof ticketCommentSchema>;
