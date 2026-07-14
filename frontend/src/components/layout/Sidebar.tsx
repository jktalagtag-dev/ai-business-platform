import { NavLink } from 'react-router-dom';
import { cn } from '@/lib/cn';
import { useAbility } from '@/hooks/useAbility';
import { useAuth } from '@/hooks/useAuth';
import { navItems, type NavItem } from '@/routes/routes.config';

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
                'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                'hover:bg-sidebar-accent',
                isActive ? 'bg-sidebar-accent text-sidebar-foreground' : 'text-sidebar-foreground/70'
              )
            }
          >
            <Icon className="h-4 w-4 shrink-0" />
            {item.label}
          </NavLink>
        );
      })}
    </nav>
  );
}

/** Persistent desktop sidebar (hidden below the lg breakpoint). */
export function Sidebar() {
  return (
    <aside className="hidden w-64 shrink-0 border-r border-sidebar-border bg-sidebar lg:block">
      <div className="flex h-14 items-center border-b border-sidebar-border px-5">
        <span className="font-semibold tracking-tight">AI Business Platform</span>
      </div>
      <SidebarNav />
    </aside>
  );
}
