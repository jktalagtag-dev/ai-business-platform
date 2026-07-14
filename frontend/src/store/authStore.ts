import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import type {
  AuthResource,
  RoleSummary,
  TenantSummary,
  UserResource,
} from '@/modules/auth/types';

interface AuthState {
  token: string | null;
  user: UserResource | null;
  tenant: TenantSummary | null;
  role: RoleSummary | null;
  permissions: string[];
  /** Set the full session after login/register. */
  setSession: (auth: AuthResource) => void;
  /** Update just the user (e.g. after a profile edit) without touching the token. */
  setUser: (user: UserResource) => void;
  /** Clear everything (logout, or a 401 from the api-client). */
  clear: () => void;
}

const empty = {
  token: null,
  user: null,
  tenant: null,
  role: null,
  permissions: [] as string[],
};

/**
 * Auth session state. Persisted to localStorage so a page refresh keeps the
 * user signed in (the Sanctum token is long-lived; there is no refresh flow).
 * This is the single source of truth the api-client reads the bearer token
 * from and that route guards / useAbility read permissions from.
 */
export const useAuthStore = create<AuthState>()(
  persist(
    (set) => ({
      ...empty,
      setSession: (auth) =>
        set({
          token: auth.token,
          user: auth.user,
          tenant: auth.membership.attributes.tenant,
          role: auth.membership.attributes.role,
          permissions: auth.membership.attributes.role.permissions,
        }),
      setUser: (user) => set({ user }),
      clear: () => set({ ...empty }),
    }),
    {
      name: 'abp.auth',
      partialize: (state) => ({
        token: state.token,
        user: state.user,
        tenant: state.tenant,
        role: state.role,
        permissions: state.permissions,
      }),
    }
  )
);

/** Non-reactive read of the current bearer token (for the api-client). */
export function getAuthToken(): string | null {
  return useAuthStore.getState().token;
}

/** Non-reactive session clear (for the api-client's 401 handler). */
export function clearAuthSession(): void {
  useAuthStore.getState().clear();
}
