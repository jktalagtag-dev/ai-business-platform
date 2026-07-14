import type { Resource } from '@/types/api';

export interface UserAttributes {
  name: string;
  email: string;
  email_verified_at: string | null;
}

export type UserResource = Resource<'user', UserAttributes>;

export interface TenantSummary {
  id: string;
  name: string;
  slug: string;
}

export interface RoleSummary {
  id: string;
  name: string;
  permissions: string[];
}

export interface MembershipAttributes {
  tenant: TenantSummary;
  role: RoleSummary;
  status: string;
}

export type MembershipResource = Resource<'tenant_membership', MembershipAttributes>;

/** `data` of a successful login/register. */
export interface AuthResource {
  token: string;
  user: UserResource;
  membership: MembershipResource;
}

/** `data` of GET /profile — the user plus all their tenant memberships. */
export interface ProfileResource {
  user: UserResource;
  memberships: MembershipResource[];
}

/** One tenant option returned inside a 409 conflict on ambiguous login. */
export interface AvailableTenant {
  id: string;
  name: string;
  slug: string;
}

// --- Request payloads (mirror the backend Form Requests) ---

export interface LoginPayload {
  email: string;
  password: string;
  tenant_slug?: string;
}

export interface RegisterPayload {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  tenant_name: string;
}

export interface ForgotPasswordPayload {
  email: string;
}

export interface ResetPasswordPayload {
  email: string;
  token: string;
  password: string;
  password_confirmation: string;
}

export interface UpdateProfilePayload {
  name: string;
  email: string;
}
