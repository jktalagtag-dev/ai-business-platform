import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { useAuth } from '@/hooks/useAuth';
import { paths } from '@/routes/routes.config';

/**
 * Wraps the entire authenticated route tree. Unauthenticated visitors are
 * redirected to /login, preserving where they were headed so login can send
 * them back. Mirrors the backend `auth:sanctum` middleware.
 */
export function RequireAuth() {
  const { isAuthenticated } = useAuth();
  const location = useLocation();

  if (!isAuthenticated) {
    return <Navigate to={paths.login} replace state={{ from: location }} />;
  }

  return <Outlet />;
}
