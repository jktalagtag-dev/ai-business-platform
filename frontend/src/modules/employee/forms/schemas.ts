import { z } from 'zod';

/**
 * Zod schemas mirroring the backend Form Requests for Employee/HR (client-side
 * UX guardrail only — the server re-validates and `error.details[]` is mapped
 * back onto these same fields via applyApiErrorsToForm).
 */

const NONE = '__none__';
export { NONE as NONE_OPTION };

export const departmentSchema = z.object({
  name: z.string().min(1, 'Name is required').max(255),
  description: z.string().optional().or(z.literal('')),
  parent_department_id: z.string().default(NONE),
  manager_employee_id: z.string().default(NONE),
});
export type DepartmentFormValues = z.infer<typeof departmentSchema>;

export const positionSchema = z.object({
  title: z.string().min(1, 'Title is required').max(255),
  description: z.string().optional().or(z.literal('')),
});
export type PositionFormValues = z.infer<typeof positionSchema>;

export const employmentTypeOptions = ['full_time', 'part_time', 'contractor', 'intern'] as const;
export const employmentStatusOptions = ['active', 'on_leave', 'suspended', 'terminated'] as const;

export const employeeSchema = z
  .object({
    first_name: z.string().min(1, 'First name is required').max(255),
    last_name: z.string().min(1, 'Last name is required').max(255),
    email: z.string().max(255).email('Enter a valid email address').optional().or(z.literal('')),
    phone: z.string().max(50).optional().or(z.literal('')),
    department_id: z.string().default(NONE),
    position_id: z.string().default(NONE),
    manager_employee_id: z.string().default(NONE),
    employment_type: z.enum(employmentTypeOptions).default('full_time'),
    employment_status: z.enum(employmentStatusOptions).default('active'),
    hire_date: z.string().min(1, 'Hire date is required'),
    termination_date: z.string().optional().or(z.literal('')),
    address_line1: z.string().max(255).optional().or(z.literal('')),
    address_city: z.string().max(255).optional().or(z.literal('')),
    address_state: z.string().max(255).optional().or(z.literal('')),
    address_postal_code: z.string().max(50).optional().or(z.literal('')),
    address_country: z.string().max(255).optional().or(z.literal('')),
    ec_name: z.string().max(255).optional().or(z.literal('')),
    ec_relationship: z.string().max(100).optional().or(z.literal('')),
    ec_phone: z.string().max(50).optional().or(z.literal('')),
    ec_email: z.string().max(255).email('Enter a valid email address').optional().or(z.literal('')),
    bio: z.string().optional().or(z.literal('')),
  })
  .superRefine((data, ctx) => {
    const ecTouched = [data.ec_name, data.ec_relationship, data.ec_phone, data.ec_email].some(
      (v) => !!v
    );
    if (ecTouched) {
      if (!data.ec_name) {
        ctx.addIssue({ code: 'custom', path: ['ec_name'], message: 'Required with an emergency contact' });
      }
      if (!data.ec_relationship) {
        ctx.addIssue({
          code: 'custom',
          path: ['ec_relationship'],
          message: 'Required with an emergency contact',
        });
      }
      if (!data.ec_phone) {
        ctx.addIssue({ code: 'custom', path: ['ec_phone'], message: 'Required with an emergency contact' });
      }
    }

    if (data.termination_date && data.hire_date) {
      if (new Date(data.termination_date) < new Date(data.hire_date)) {
        ctx.addIssue({
          code: 'custom',
          path: ['termination_date'],
          message: 'Termination date must be on or after the hire date',
        });
      }
    }
  });
export type EmployeeFormValues = z.infer<typeof employeeSchema>;

export const employeeNoteSchema = z.object({
  note: z.string().min(1, 'Note cannot be empty').max(5000),
});
export type EmployeeNoteFormValues = z.infer<typeof employeeNoteSchema>;
