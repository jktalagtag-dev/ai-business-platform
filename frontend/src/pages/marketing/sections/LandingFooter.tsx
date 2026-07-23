import { Link } from 'react-router-dom';
import { paths } from '@/routes/routes.config';

export function LandingFooter() {
  return (
    <footer className="border-t">
      <div className="mx-auto flex w-full max-w-[1280px] flex-col items-center justify-between gap-4 px-4 py-8 text-sm text-muted-foreground sm:flex-row sm:px-6 lg:px-8">
        <p>&copy; {new Date().getFullYear()} AI Business Platform. All rights reserved.</p>
        <nav className="flex items-center gap-6">
          <Link to={paths.login} className="hover:text-foreground">
            Log in
          </Link>
          <Link to={paths.register} className="hover:text-foreground">
            Get started
          </Link>
        </nav>
      </div>
    </footer>
  );
}
