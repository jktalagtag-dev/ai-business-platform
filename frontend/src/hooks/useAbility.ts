import { useAuthStore } from '@/store/authStore';

/**
 * Client-side mirror of the backend RBAC check: does the current session hold
 * a given permission key (e.g. `products.manage`)? Used by route guards and to
 * hide/disable actions a user's role can't perform — "an action the user's
 * role can't do is never rendered, not just disabled."
 */
export function useAbility(): (permission: string) => boolean;
export function useAbility(permission: string): boolean;
export function useAbility(permission?: string): boolean | ((permission: string) => boolean) {
  const permissions = useAuthStore((s) => s.permissions);
  const can = (p: string) => permissions.includes(p);
  return permission === undefined ? can : can(permission);
}
