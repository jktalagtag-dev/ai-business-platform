import type { AuthResource } from '@/modules/auth/types';
import type {
  CategoryResource,
  InventoryItemResource,
  ProductResource,
  SupplierResource,
} from '@/modules/inventory/types';
import type { DepartmentResource, EmployeeResource, PositionResource } from '@/modules/employee/types';
import type { TicketResource } from '@/modules/ticket/types';
import type { AiConversationResource, AiMessageResource } from '@/modules/ai/types';
import type { KbDocumentResource } from '@/modules/kb/types';
import type {
  AutomationJobResource,
  WorkflowResource,
  WorkflowStepResource,
} from '@/modules/automation/types';

export function makeAuthResource(overrides?: {
  permissions?: string[];
  roleName?: string;
}): AuthResource {
  return {
    token: 'test-token',
    user: {
      id: 'user_1',
      type: 'user',
      attributes: { name: 'Ada Lovelace', email: 'ada@example.com', email_verified_at: null },
    },
    membership: {
      id: 'membership_1',
      type: 'tenant_membership',
      attributes: {
        tenant: { id: 'tenant_1', name: 'Analytical Engines', slug: 'analytical-engines' },
        role: {
          id: 'role_1',
          name: overrides?.roleName ?? 'Owner',
          permissions: overrides?.permissions ?? ['products.view', 'products.manage'],
        },
        status: 'active',
      },
    },
  };
}

export function makeCategoryResource(overrides?: Partial<CategoryResource['attributes']> & { id?: string }): CategoryResource {
  return {
    id: overrides?.id ?? 'category_1',
    type: 'category',
    attributes: { name: 'Electronics', parent_category_id: null, ...overrides },
  };
}

export function makeProductResource(overrides?: Partial<ProductResource['attributes']> & { id?: string }): ProductResource {
  return {
    id: overrides?.id ?? 'product_1',
    type: 'product',
    attributes: {
      sku: 'WIDGET-001',
      name: 'Widget',
      description: null,
      category_id: null,
      unit_price: '19.99',
      cost_price: '9.50',
      is_active: true,
      ...overrides,
    },
  };
}

export function makeSupplierResource(overrides?: Partial<SupplierResource['attributes']> & { id?: string }): SupplierResource {
  return {
    id: overrides?.id ?? 'supplier_1',
    type: 'supplier',
    attributes: {
      name: 'Acme Supplies Ltd.',
      contact_email: null,
      contact_phone: null,
      address: null,
      status: 'active',
      ...overrides,
    },
  };
}

export function makeInventoryItemResource(
  overrides?: Partial<InventoryItemResource['attributes']> & { id?: string }
): InventoryItemResource {
  return {
    id: overrides?.id ?? 'inventory_item_1',
    type: 'inventory_item',
    attributes: {
      product_id: 'product_1',
      product_sku: 'WIDGET-001',
      product_name: 'Widget',
      quantity_on_hand: 120,
      quantity_reserved: 5,
      reorder_point: 10,
      reorder_quantity: 50,
      is_low_stock: false,
      ...overrides,
    },
  };
}

export function makeDepartmentResource(
  overrides?: Partial<DepartmentResource['attributes']> & { id?: string }
): DepartmentResource {
  return {
    id: overrides?.id ?? 'department_1',
    type: 'department',
    attributes: {
      name: 'Engineering',
      description: null,
      parent_department_id: null,
      manager_employee_id: null,
      ...overrides,
    },
  };
}

export function makePositionResource(
  overrides?: Partial<PositionResource['attributes']> & { id?: string }
): PositionResource {
  return {
    id: overrides?.id ?? 'position_1',
    type: 'position',
    attributes: { title: 'Software Engineer', description: null, ...overrides },
  };
}

export function makeEmployeeResource(
  overrides?: Partial<EmployeeResource['attributes']> & { id?: string }
): EmployeeResource {
  return {
    id: overrides?.id ?? 'employee_1',
    type: 'employee',
    attributes: {
      employee_number: 'EMP-000123',
      first_name: 'Jane',
      last_name: 'Doe',
      full_name: 'Jane Doe',
      email: 'jane@example.com',
      phone: null,
      department_id: null,
      position_id: null,
      manager_employee_id: null,
      employment_type: 'full_time',
      employment_status: 'active',
      hire_date: '2025-01-15',
      termination_date: null,
      address: null,
      emergency_contact: null,
      avatar_url: null,
      bio: null,
      ...overrides,
    },
  };
}

export function makeTicketResource(
  overrides?: Partial<TicketResource['attributes']> & { id?: string }
): TicketResource {
  return {
    id: overrides?.id ?? 'ticket_1',
    type: 'ticket',
    attributes: {
      ticket_number: 'TCK-000123',
      employee_id: 'employee_1',
      assigned_technician_id: null,
      department_id: null,
      ticket_type: 'hardware',
      priority: 'medium',
      status: 'open',
      subject: "Laptop won't boot",
      description: 'Pressed the power button and nothing happens.',
      resolution_notes: null,
      resolved_at: null,
      closed_at: null,
      sla_breached_at: null,
      created_at: '2026-07-13T10:00:00+00:00',
      ...overrides,
    },
  };
}

export function makeAiConversationResource(
  overrides?: Partial<AiConversationResource['attributes']> & { id?: string }
): AiConversationResource {
  return {
    id: overrides?.id ?? 'conversation_1',
    type: 'ai_conversation',
    attributes: {
      title: null,
      system_prompt: null,
      provider: 'openai',
      model: 'gpt-4o-mini',
      total_prompt_tokens: 0,
      total_completion_tokens: 0,
      created_at: '2026-07-13T10:00:00+00:00',
      updated_at: '2026-07-13T10:00:00+00:00',
      ...overrides,
    },
  };
}

export function makeAiMessageResource(
  overrides?: Partial<AiMessageResource['attributes']> & { id?: string }
): AiMessageResource {
  return {
    id: overrides?.id ?? 'message_1',
    type: 'ai_message',
    attributes: {
      role: 'user',
      content: 'Hello',
      tool_calls: null,
      tool_call_id: null,
      name: null,
      prompt_tokens: null,
      completion_tokens: null,
      created_at: '2026-07-13T10:00:00+00:00',
      ...overrides,
    },
  };
}

export function makeKbDocumentResource(
  overrides?: Partial<KbDocumentResource['attributes']> & { id?: string }
): KbDocumentResource {
  return {
    id: overrides?.id ?? 'document_1',
    type: 'kb_document',
    attributes: {
      title: 'Employee Handbook',
      original_filename: 'handbook.pdf',
      mime_type: 'application/pdf',
      size_bytes: 204800,
      status: 'ready',
      error_message: null,
      page_count: 2,
      created_at: '2026-07-13T10:00:00+00:00',
      updated_at: '2026-07-13T10:00:05+00:00',
      ...overrides,
    },
  };
}

export function makeWorkflowResource(
  overrides?: Partial<WorkflowResource['attributes']> & { id?: string }
): WorkflowResource {
  return {
    id: overrides?.id ?? 'workflow_1',
    type: 'workflow',
    attributes: {
      name: 'Notify ops on critical ticket',
      description: 'Sends an email whenever a critical-priority ticket is created.',
      status: 'draft',
      created_by_user_id: 'user_1',
      last_triggered_at: null,
      created_at: '2026-07-13T10:00:00+00:00',
      updated_at: '2026-07-13T10:00:00+00:00',
      ...overrides,
    },
  };
}

export function makeWorkflowStepResource(
  overrides?: Partial<WorkflowStepResource['attributes']> & { id?: string }
): WorkflowStepResource {
  return {
    id: overrides?.id ?? 'step_1',
    type: 'workflow_step',
    attributes: {
      step_order: 0,
      step_type: 'trigger',
      config: { kind: 'event', event: 'ticket.created' },
      ...overrides,
    },
  };
}

export function makeAutomationJobResource(
  overrides?: Partial<AutomationJobResource['attributes']> & { id?: string }
): AutomationJobResource {
  return {
    id: overrides?.id ?? 'job_1',
    type: 'automation_job',
    attributes: {
      workflow_id: 'workflow_1',
      trigger: 'ticket.created',
      status: 'succeeded',
      attempts: 1,
      max_attempts: 3,
      context: {},
      error: null,
      scheduled_at: '2026-07-13T10:00:00+00:00',
      started_at: '2026-07-13T10:00:01+00:00',
      finished_at: '2026-07-13T10:00:02+00:00',
      created_at: '2026-07-13T10:00:00+00:00',
      updated_at: '2026-07-13T10:00:02+00:00',
      ...overrides,
    },
  };
}
