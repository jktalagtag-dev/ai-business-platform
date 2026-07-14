import { describe, it, expect } from 'vitest';
import {
  assignTicketSchema,
  closeTicketSchema,
  NONE_OPTION,
  ticketSchema,
  updateTicketStatusSchema,
} from '@/modules/ticket/forms/schemas';

describe('ticketSchema', () => {
  it('requires a subject and description', () => {
    const result = ticketSchema.safeParse({
      employee_id: NONE_OPTION,
      type: 'hardware',
      priority: 'medium',
      subject: '',
      description: '',
    });
    expect(result.success).toBe(false);
  });

  it('accepts a valid ticket', () => {
    const result = ticketSchema.safeParse({
      employee_id: NONE_OPTION,
      type: 'hardware',
      priority: 'medium',
      subject: "Laptop won't boot",
      description: 'Pressed the power button and nothing happens.',
    });
    expect(result.success).toBe(true);
  });
});

describe('assignTicketSchema', () => {
  it('rejects the none sentinel — a technician must actually be chosen', () => {
    const result = assignTicketSchema.safeParse({ technician_employee_id: NONE_OPTION });
    expect(result.success).toBe(false);
  });

  it('accepts a real employee id', () => {
    const result = assignTicketSchema.safeParse({ technician_employee_id: 'employee_2' });
    expect(result.success).toBe(true);
  });
});

describe('updateTicketStatusSchema', () => {
  it('rejects "closed" — only POST /close can set that status', () => {
    const result = updateTicketStatusSchema.safeParse({ status: 'closed' });
    expect(result.success).toBe(false);
  });

  it('accepts a settable status', () => {
    const result = updateTicketStatusSchema.safeParse({ status: 'resolved' });
    expect(result.success).toBe(true);
  });
});

describe('closeTicketSchema', () => {
  it('requires resolution notes', () => {
    expect(closeTicketSchema.safeParse({ resolution_notes: '' }).success).toBe(false);
    expect(closeTicketSchema.safeParse({ resolution_notes: 'Replaced the battery.' }).success).toBe(true);
  });
});
