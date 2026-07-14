import { Navigate, Outlet } from 'react-router-dom';
import { useAuth } from '@/hooks/useAuth';
import { paths } from '@/routes/routes.config';

/** Keeps signed-in users out of the auth pages (login/register/etc.). */
export function RedirectIfAuthenticated() {
  const { isAuthenticated } = useAuth();
  if (isAuthenticated) return <Navigate to={paths.dashboard} replace />;
  return <Outlet />;
}
