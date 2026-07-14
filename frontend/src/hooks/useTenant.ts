import { useAuthStore } from '@/store/authStore';

/** The tenant the current token is scoped to (for display/branding). */
export function useTenant() {
  return useAuthStore((s) => s.tenant);
}
