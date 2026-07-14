import { Link } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { paths } from '@/routes/routes.config';

export function NotFoundPage() {
  return (
    <div className="flex min-h-svh flex-col items-center justify-center px-4 text-center">
      <p className="text-5xl font-bold tracking-tight">404</p>
      <h1 className="mt-2 text-xl font-semibold">Page not found</h1>
      <p className="mt-1 max-w-sm text-sm text-muted-foreground">
        The page you're looking for doesn't exist or has moved.
      </p>
      <Button asChild className="mt-6">
        <Link to={paths.dashboard}>Back to dashboard</Link>
      </Button>
    </div>
  );
}
