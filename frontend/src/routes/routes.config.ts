import {
  LayoutDashboard,
  Boxes,
  Users,
  Building2,
  IdCard,
  Ticket,
  MessageSquare,
  BookOpen,
  Workflow,
  ListChecks,
  ScrollText,
  type LucideIcon,
} from 'lucide-react';

/** Canonical path constants — the only place route strings are written. */
export const paths = {
  login: '/login',
  register: '/register',
  forgotPassword: '/forgot-password',
  resetPassword: '/reset-password',

  dashboard: '/',
  profile: '/settings/profile',

  inventory: '/inventory',
  employees: '/hr/employees',
  departments: '/hr/departments',
  myEmployeeProfile: '/hr/my-profile',
  tickets: '/tickets',
  aiConversations: '/ai/conversations',
  knowledgeBase: '/knowledge-base',
  automation: '/automation/workflows',
  automationJobs: '/automation/jobs',
  auditLog: '/settings/audit-log',

  forbidden: '/403',
} as const;

export interface NavItem {
  label: string;
  path: string;
  icon: LucideIcon;
  /** Permission key required to see this item (mirrors backend policy). */
  ability?: string;
  /** Any-of permission keys, for an item whose sub-areas have distinct abilities. */
  abilities?: string[];
  /** Role names allowed (for role-gated routes like the audit log). */
  roles?: string[];
}

/**
 * Primary sidebar navigation. Gating mirrors the backend: an item a user's
 * token can't call is not rendered. Items without an `ability`/`roles` are
 * reachable by any authenticated member (e.g. the AI assistant, or Tickets
 * where the backend scopes a plain member to their own tickets).
 */
export const navItems: NavItem[] = [
  { label: 'Dashboard', path: paths.dashboard, icon: LayoutDashboard },
  {
    label: 'Inventory',
    path: paths.inventory,
    icon: Boxes,
    abilities: ['products.view', 'categories.view', 'suppliers.view', 'inventory.view'],
  },
  { label: 'Employees', path: paths.employees, icon: Users, ability: 'employees.view' },
  {
    label: 'Departments',
    path: paths.departments,
    icon: Building2,
    abilities: ['departments.view', 'positions.view'],
  },
  { label: 'My Profile', path: paths.myEmployeeProfile, icon: IdCard },
  { label: 'Tickets', path: paths.tickets, icon: Ticket },
  { label: 'AI Assistant', path: paths.aiConversations, icon: MessageSquare },
  {
    label: 'Knowledge Base',
    path: paths.knowledgeBase,
    icon: BookOpen,
    ability: 'knowledge_base.view',
  },
  { label: 'Automation', path: paths.automation, icon: Workflow, ability: 'automation.view' },
  {
    label: 'Automation Jobs',
    path: paths.automationJobs,
    icon: ListChecks,
    ability: 'automation.view',
  },
  {
    label: 'Audit Log',
    path: paths.auditLog,
    icon: ScrollText,
    roles: ['Owner', 'Admin'],
  },
];
