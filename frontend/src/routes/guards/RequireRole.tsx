import { Navigate, Outlet } from 'react-router-dom';
import { useAuth } from '@/hooks/useAuth';
import { paths } from '@/routes/routes.config';

/**
 * Wraps route branches gated by role name rather than a permission key —
 * currently only the Audit Log, which the backend restricts via
 * `role:owner,admin` middleware instead of an ability check (there is no
 * `audit.view` permission). A user whose role isn't in `roles` is sent to
 * /403, same as `RequireAbility`.
 */
export function RequireRole({ roles }: { roles: string[] }) {
  const { role } = useAuth();

  const allowed = !!role && roles.includes(role.name);

  if (!allowed) {
    return <Navigate to={paths.forbidden} replace />;
  }

  return <Outlet />;
}
