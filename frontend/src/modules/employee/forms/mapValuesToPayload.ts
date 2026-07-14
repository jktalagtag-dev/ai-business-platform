import { NONE_OPTION } from '@/modules/employee/forms/schemas';
import type { EmployeeFormValues } from '@/modules/employee/forms/schemas';
import type { EmployeeResource, UpdateEmployeePayload } from '@/modules/employee/types';

/** Populate the form when editing an existing employee (or blank defaults for create). */
export function employeeResourceToFormValues(employee?: EmployeeResource): EmployeeFormValues {
  const a = employee?.attributes;
  const ec = a?.emergency_contact;
  const addr = a?.address;
  return {
    first_name: a?.first_name ?? '',
    last_name: a?.last_name ?? '',
    email: a?.email ?? '',
    phone: a?.phone ?? '',
    department_id: a?.department_id ?? NONE_OPTION,
    position_id: a?.position_id ?? NONE_OPTION,
    manager_employee_id: a?.manager_employee_id ?? NONE_OPTION,
    employment_type: a?.employment_type ?? 'full_time',
    employment_status: a?.employment_status ?? 'active',
    hire_date: a?.hire_date ?? '',
    termination_date: a?.termination_date ?? '',
    address_line1: addr?.line1 ?? '',
    address_city: addr?.city ?? '',
    address_state: addr?.state ?? '',
    address_postal_code: addr?.postal_code ?? '',
    address_country: addr?.country ?? '',
    ec_name: ec?.name ?? '',
    ec_relationship: ec?.relationship ?? '',
    ec_phone: ec?.phone ?? '',
    ec_email: ec?.email ?? '',
    bio: a?.bio ?? '',
  };
}

/** Shared by create and edit — create simply drops `termination_date` before
 * sending, since it's meaningless for a new hire and isn't in the Store
 * Form Request's rule set. */
export function employeeFormValuesToPayload(values: EmployeeFormValues): UpdateEmployeePayload {
  const ecTouched = [values.ec_name, values.ec_relationship, values.ec_phone, values.ec_email].some(
    (v) => !!v
  );

  const address = {
    line1: values.address_line1 || undefined,
    city: values.address_city || undefined,
    state: values.address_state || undefined,
    postal_code: values.address_postal_code || undefined,
    country: values.address_country || undefined,
  };
  const hasAddress = Object.values(address).some((v) => v !== undefined);

  return {
    first_name: values.first_name,
    last_name: values.last_name,
    email: values.email || null,
    phone: values.phone || null,
    department_id: values.department_id === NONE_OPTION ? null : values.department_id,
    position_id: values.position_id === NONE_OPTION ? null : values.position_id,
    manager_employee_id:
      values.manager_employee_id === NONE_OPTION ? null : values.manager_employee_id,
    employment_type: values.employment_type,
    employment_status: values.employment_status,
    hire_date: values.hire_date,
    termination_date: values.termination_date || null,
    address: hasAddress ? address : null,
    emergency_contact: ecTouched
      ? {
          name: values.ec_name ?? '',
          relationship: values.ec_relationship ?? '',
          phone: values.ec_phone ?? '',
          email: values.ec_email || null,
        }
      : null,
    bio: values.bio || null,
  };
}
