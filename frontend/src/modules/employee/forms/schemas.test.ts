import { describe, it, expect } from 'vitest';
import { departmentSchema, employeeSchema, positionSchema } from '@/modules/employee/forms/schemas';

describe('departmentSchema', () => {
  it('requires a name', () => {
    expect(
      departmentSchema.safeParse({ name: '', parent_department_id: '__none__', manager_employee_id: '__none__' })
        .success
    ).toBe(false);
  });

  it('accepts a valid department', () => {
    expect(
      departmentSchema.safeParse({
        name: 'Engineering',
        parent_department_id: '__none__',
        manager_employee_id: '__none__',
      }).success
    ).toBe(true);
  });
});

describe('positionSchema', () => {
  it('requires a title', () => {
    expect(positionSchema.safeParse({ title: '' }).success).toBe(false);
  });
});

function baseEmployee(overrides: Record<string, unknown> = {}) {
  return {
    first_name: 'Jane',
    last_name: 'Doe',
    department_id: '__none__',
    position_id: '__none__',
    manager_employee_id: '__none__',
    employment_type: 'full_time',
    employment_status: 'active',
    hire_date: '2025-01-15',
    ...overrides,
  };
}

describe('employeeSchema', () => {
  it('requires first and last name', () => {
    expect(employeeSchema.safeParse(baseEmployee({ first_name: '' })).success).toBe(false);
  });

  it('requires a hire date', () => {
    expect(employeeSchema.safeParse(baseEmployee({ hire_date: '' })).success).toBe(false);
  });

  it('rejects a termination date before the hire date', () => {
    const result = employeeSchema.safeParse(
      baseEmployee({ hire_date: '2025-06-01', termination_date: '2025-01-01' })
    );
    expect(result.success).toBe(false);
  });

  it('accepts a termination date on or after the hire date', () => {
    const result = employeeSchema.safeParse(
      baseEmployee({ hire_date: '2025-01-15', termination_date: '2025-06-01' })
    );
    expect(result.success).toBe(true);
  });

  it('requires name/relationship/phone once any emergency contact field is touched', () => {
    const result = employeeSchema.safeParse(baseEmployee({ ec_phone: '555-0100' }));
    expect(result.success).toBe(false);
  });

  it('accepts a fully-filled emergency contact', () => {
    const result = employeeSchema.safeParse(
      baseEmployee({ ec_name: 'John Doe', ec_relationship: 'Spouse', ec_phone: '555-0100' })
    );
    expect(result.success).toBe(true);
  });

  it('accepts no emergency contact at all', () => {
    expect(employeeSchema.safeParse(baseEmployee()).success).toBe(true);
  });
});
