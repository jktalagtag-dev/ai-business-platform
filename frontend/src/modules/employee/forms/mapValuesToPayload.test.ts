import { describe, it, expect } from 'vitest';
import {
  employeeFormValuesToPayload,
  employeeResourceToFormValues,
} from '@/modules/employee/forms/mapValuesToPayload';
import { employeeSchema } from '@/modules/employee/forms/schemas';
import { makeEmployeeResource } from '@/tests/fixtures';

function parse(values: Record<string, unknown>) {
  return employeeSchema.parse(values);
}

describe('employeeResourceToFormValues / employeeFormValuesToPayload', () => {
  it('converts null relationship fields to the none sentinel for the select inputs', () => {
    const values = employeeResourceToFormValues(makeEmployeeResource());
    expect(values.department_id).toBe('__none__');
    expect(values.position_id).toBe('__none__');
    expect(values.manager_employee_id).toBe('__none__');
  });

  it('round-trips the sentinel back to null and omits an untouched emergency contact', () => {
    const values = parse(employeeResourceToFormValues(makeEmployeeResource()));
    const payload = employeeFormValuesToPayload(values);

    expect(payload.department_id).toBeNull();
    expect(payload.position_id).toBeNull();
    expect(payload.manager_employee_id).toBeNull();
    expect(payload.emergency_contact).toBeNull();
    expect(payload.address).toBeNull();
  });

  it('assembles a full emergency contact object once touched', () => {
    const values = parse({
      first_name: 'Jane',
      last_name: 'Doe',
      department_id: '__none__',
      position_id: '__none__',
      manager_employee_id: '__none__',
      employment_type: 'full_time',
      employment_status: 'active',
      hire_date: '2025-01-15',
      ec_name: 'John Doe',
      ec_relationship: 'Spouse',
      ec_phone: '555-0100',
    });
    const payload = employeeFormValuesToPayload(values);

    expect(payload.emergency_contact).toEqual({
      name: 'John Doe',
      relationship: 'Spouse',
      phone: '555-0100',
      email: null,
    });
  });

  it('preserves an existing department/manager id rather than the sentinel', () => {
    const employee = makeEmployeeResource({ department_id: 'dept_1', manager_employee_id: 'mgr_1' });
    const values = parse(employeeResourceToFormValues(employee));
    const payload = employeeFormValuesToPayload(values);

    expect(payload.department_id).toBe('dept_1');
    expect(payload.manager_employee_id).toBe('mgr_1');
  });
});
