import { useAuthStore } from '@/store/authStore';

/** Reactive access to the current auth session. */
export function useAuth() {
  const token = useAuthStore((s) => s.token);
  const user = useAuthStore((s) => s.user);
  const tenant = useAuthStore((s) => s.tenant);
  const role = useAuthStore((s) => s.role);

  return {
    token,
    user,
    tenant,
    role,
    isAuthenticated: token !== null,
  };
}
