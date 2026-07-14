import { Navigate, Outlet } from 'react-router-dom';
import { useAbility } from '@/hooks/useAbility';
import { paths } from '@/routes/routes.config';

/**
 * Wraps admin-only route branches. A user missing the required permission is
 * sent to /403. Mirrors the backend policy check — "a route a user can't
 * reach here is also a route their token can't call."
 *
 * Pass `abilities` (any-of) for a route whose sub-areas are gated by
 * different permissions — e.g. /inventory is reachable by a role with just
 * `inventory.view` even without `products.view`.
 */
export function RequireAbility({
  ability,
  abilities,
}: {
  ability?: string;
  abilities?: string[];
}) {
  const can = useAbility();

  const allowed = ability ? can(ability) : (abilities ?? []).some((a) => can(a));

  if (!allowed) {
    return <Navigate to={paths.forbidden} replace />;
  }

  return <Outlet />;
}
