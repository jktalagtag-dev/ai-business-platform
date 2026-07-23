import { Link } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { paths } from '@/routes/routes.config';

export function LandingHeader() {
  return (
    <header className="sticky top-0 z-10 border-b bg-background/95 backdrop-blur">
      <div className="mx-auto flex h-16 w-full max-w-[1280px] items-center justify-between px-4 sm:px-6 lg:px-8">
        <Link to={paths.dashboard} className="text-title font-bold tracking-tight">
          AI Business Platform
        </Link>
        <nav className="flex items-center gap-2">
          <Button variant="ghost" asChild>
            <Link to={paths.login}>Log in</Link>
          </Button>
          <Button asChild>
            <Link to={paths.register}>Get started</Link>
          </Button>
        </nav>
      </div>
    </header>
  );
}
