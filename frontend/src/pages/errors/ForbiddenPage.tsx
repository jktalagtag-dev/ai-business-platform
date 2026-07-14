import { Link } from 'react-router-dom';
import { ShieldX } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { paths } from '@/routes/routes.config';

export function ForbiddenPage() {
  return (
    <div className="flex min-h-[60vh] flex-col items-center justify-center text-center">
      <ShieldX className="mb-4 h-12 w-12 text-muted-foreground" />
      <h1 className="text-2xl font-semibold">Access denied</h1>
      <p className="mt-1 max-w-sm text-sm text-muted-foreground">
        You don't have permission to view this page.
      </p>
      <Button asChild className="mt-6">
        <Link to={paths.dashboard}>Back to dashboard</Link>
      </Button>
    </div>
  );
}
