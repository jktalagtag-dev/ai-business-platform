import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { useAuth } from '@/hooks/useAuth';
import { LandingPage } from '@/pages/marketing/LandingPage';
import { paths } from '@/routes/routes.config';

/**
 * Wraps the entire authenticated route tree. Unauthenticated visitors are
 * redirected to /login, preserving where they were headed so login can send
 * them back — mirrors the backend `auth:sanctum` middleware — with one
 * exception: the bare root path is public. An unauthenticated visitor there
 * sees the marketing LandingPage (no AppLayout chrome, since it renders here
 * instead of the guarded route tree) rather than being bounced to /login;
 * every other authenticated path is unaffected.
 */
export function RequireAuth() {
  const { isAuthenticated } = useAuth();
  const location = useLocation();

  if (!isAuthenticated) {
    if (location.pathname === paths.dashboard) {
      return <LandingPage />;
    }
    return <Navigate to={paths.login} replace state={{ from: location }} />;
  }

  return <Outlet />;
}
