import { Outlet } from 'react-router-dom';

/** Centered, chrome-free layout for the public auth pages. */
export function AuthLayout() {
  return (
    <div className="flex min-h-svh flex-col items-center justify-center bg-muted/40 px-4 py-10">
      <div className="mb-6 text-center">
        <h1 className="text-xl font-semibold tracking-tight">AI Business Platform</h1>
      </div>
      <div className="w-full max-w-sm">
        <Outlet />
      </div>
    </div>
  );
}
