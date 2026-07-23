import { Link, NavLink, useNavigate } from 'react-router-dom';
import { LogOut } from 'lucide-react';
import { cn } from '@/lib/cn';
import { initials } from '@/lib/initials';
import { useAbility } from '@/hooks/useAbility';
import { useAuth } from '@/hooks/useAuth';
import { useLogout } from '@/modules/auth/hooks/useLogout';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { navItems, paths, type NavItem } from '@/routes/routes.config';

function useVisibleNavItems(): NavItem[] {
  const can = useAbility();
  const { role } = useAuth();

  return navItems.filter((item) => {
    if (item.ability && !can(item.ability)) return false;
    if (item.abilities && !item.abilities.some((a) => can(a))) return false;
    if (item.roles && !(role && item.roles.includes(role.name))) return false;
    return true;
  });
}

/** The navigation list, shared by the desktop sidebar and the mobile drawer. */
export function SidebarNav({ onNavigate }: { onNavigate?: () => void }) {
  const items = useVisibleNavItems();

  return (
    <nav className="flex flex-col gap-1 p-3">
      {items.map((item) => {
        const Icon = item.icon;
        return (
          <NavLink
            key={item.path}
            to={item.path}
            end={item.path === '/'}
            onClick={onNavigate}
            className={({ isActive }) =>
              cn(
                'flex items-center gap-3 rounded-md border-l-2 px-3 py-2.5 text-sm font-medium transition-colors duration-150',
                'hover:bg-sidebar-accent/60',
                isActive
                  ? 'border-primary bg-sidebar-accent/60 text-sidebar-foreground'
                  : 'border-transparent text-sidebar-foreground/70'
              )
            }
          >
            {({ isActive }) => (
              <>
                <Icon
                  className={cn('h-6 w-6 shrink-0', isActive && 'text-primary')}
                  strokeWidth={1.75}
                />
                {item.label}
              </>
            )}
          </NavLink>
        );
      })}
    </nav>
  );
}

/** Compact profile + logout footer, pinned to the bottom of the desktop
 * sidebar (DESIGN_SYSTEM.md's sidebar spec: nav, then Settings/Profile/
 * Logout). Theme switching and the fuller account menu stay in the
 * Topbar's dropdown — this is just the always-visible identity + exit. */
function SidebarFooter() {
  const navigate = useNavigate();
  const { user, role } = useAuth();
  const logout = useLogout();

  const onLogout = () => {
    logout.mutate(undefined, {
      onSettled: () => navigate(paths.login, { replace: true }),
    });
  };

  return (
    <div className="mt-auto border-t border-sidebar-border p-3">
      <div className="flex items-center gap-2">
        <Link
          to={paths.profile}
          className="flex min-w-0 flex-1 items-center gap-3 rounded-md px-2 py-2 transition-colors duration-150 hover:bg-sidebar-accent/60"
        >
          <Avatar className="h-8 w-8 shrink-0">
            <AvatarFallback>{user ? initials(user.attributes.name) : '?'}</AvatarFallback>
          </Avatar>
          <span className="min-w-0 flex-1">
            <span className="block truncate text-sm font-medium text-sidebar-foreground">
              {user?.attributes.name}
            </span>
            {role && (
              <span className="block truncate text-xs text-sidebar-foreground/60">
                {role.name}
              </span>
            )}
          </span>
        </Link>
        <Button
          variant="ghost"
          size="icon"
          className="h-8 w-8 shrink-0"
          aria-label="Log out"
          onClick={onLogout}
        >
          <LogOut className="h-4 w-4" strokeWidth={1.75} />
        </Button>
      </div>
    </div>
  );
}

/** Persistent desktop sidebar (hidden below the lg breakpoint). */
export function Sidebar() {
  return (
    <aside className="hidden w-[280px] shrink-0 flex-col border-r border-sidebar-border bg-sidebar lg:flex">
      <div className="flex h-16 items-center border-b border-sidebar-border px-5">
        <span className="font-semibold tracking-tight">AI Business Platform</span>
      </div>
      <SidebarNav />
      <SidebarFooter />
    </aside>
  );
}
