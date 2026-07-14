import type { Resource } from '@/types/api';

// --- Departments ---

export interface DepartmentAttributes {
  name: string;
  description: string | null;
  parent_department_id: string | null;
  manager_employee_id: string | null;
}

export type DepartmentResource = Resource<'department', DepartmentAttributes>;

export interface DepartmentPayload {
  name: string;
  description?: string | null;
  parent_department_id?: string | null;
  manager_employee_id?: string | null;
}

// --- Positions ---

export interface PositionAttributes {
  title: string;
  description: string | null;
}

export type PositionResource = Resource<'position', PositionAttributes>;

export interface PositionPayload {
  title: string;
  description?: string | null;
}

// --- Employees ---

export type EmploymentType = 'full_time' | 'part_time' | 'contractor' | 'intern';
export type EmploymentStatus = 'active' | 'on_leave' | 'suspended' | 'terminated';

export interface EmergencyContact {
  name: string;
  relationship: string;
  phone: string;
  email?: string | null;
}

export interface EmployeeAddress {
  line1?: string;
  city?: string;
  state?: string;
  postal_code?: string;
  country?: string;
}

export interface EmployeeAttributes {
  employee_number: string;
  first_name: string;
  last_name: string;
  full_name: string;
  email: string | null;
  phone: string | null;
  department_id: string | null;
  position_id: string | null;
  manager_employee_id: string | null;
  employment_type: EmploymentType;
  employment_status: EmploymentStatus;
  hire_date: string;
  termination_date: string | null;
  address: EmployeeAddress | null;
  emergency_contact: EmergencyContact | null;
  avatar_url: string | null;
  bio: string | null;
}

export type EmployeeResource = Resource<'employee', EmployeeAttributes>;

/** No `user_id` or `termination_date` — linking a user account isn't
 * supported by this frontend slice (no member-listing endpoint exists yet),
 * and termination_date is meaningless for a brand-new hire. */
export interface CreateEmployeePayload {
  first_name: string;
  last_name: string;
  email?: string | null;
  phone?: string | null;
  department_id?: string | null;
  position_id?: string | null;
  manager_employee_id?: string | null;
  employment_type?: EmploymentType;
  employment_status?: EmploymentStatus;
  hire_date: string;
  address?: EmployeeAddress | null;
  emergency_contact?: EmergencyContact | null;
  bio?: string | null;
}

export interface UpdateEmployeePayload extends CreateEmployeePayload {
  termination_date?: string | null;
}

export interface EmployeeListParams {
  department_id?: string;
  position_id?: string;
  employment_status?: EmploymentStatus;
  manager_employee_id?: string;
  search?: string;
  sort?: 'first_name' | 'last_name' | 'hire_date' | 'created_at';
  direction?: 'asc' | 'desc';
  per_page?: number;
  cursor?: string;
}

// --- Employee notes ---
// Create + list only — there is no show/update/delete endpoint for notes.

export interface EmployeeNoteAttributes {
  employee_id: string;
  author_user_id: string | null;
  note: string;
  created_at: string;
}

export type EmployeeNoteResource = Resource<'employee_note', EmployeeNoteAttributes>;

export interface CreateEmployeeNotePayload {
  note: string;
}
