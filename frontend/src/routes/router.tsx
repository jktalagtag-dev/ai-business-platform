import { createBrowserRouter } from 'react-router-dom';
import { AuthLayout } from '@/layouts/AuthLayout';
import { AppLayout } from '@/layouts/AppLayout';
import { RequireAuth } from '@/routes/guards/RequireAuth';
import { RequireAbility } from '@/routes/guards/RequireAbility';
import { RedirectIfAuthenticated } from '@/routes/guards/RedirectIfAuthenticated';
import { ComingSoonPage } from '@/pages/ComingSoonPage';
import { ForbiddenPage } from '@/pages/errors/ForbiddenPage';
import { NotFoundPage } from '@/pages/errors/NotFoundPage';
import { paths } from '@/routes/routes.config';

// Auth pages and the authenticated content pages are code-split per route.
const lazyLogin = () => import('@/pages/auth/LoginPage').then((m) => ({ Component: m.LoginPage }));
const lazyRegister = () =>
  import('@/pages/auth/RegisterPage').then((m) => ({ Component: m.RegisterPage }));
const lazyForgot = () =>
  import('@/pages/auth/ForgotPasswordPage').then((m) => ({ Component: m.ForgotPasswordPage }));
const lazyReset = () =>
  import('@/pages/auth/ResetPasswordPage').then((m) => ({ Component: m.ResetPasswordPage }));
const lazyDashboard = () =>
  import('@/pages/DashboardPage').then((m) => ({ Component: m.DashboardPage }));
const lazyProfile = () =>
  import('@/pages/settings/ProfilePage').then((m) => ({ Component: m.ProfilePage }));
const lazyInventory = () =>
  import('@/pages/inventory/InventoryPage').then((m) => ({ Component: m.InventoryPage }));
const lazyHR = () => import('@/pages/hr/HRPage').then((m) => ({ Component: m.HRPage }));
const lazyEmployeesList = () =>
  import('@/pages/hr/EmployeesListPage').then((m) => ({ Component: m.EmployeesListPage }));
const lazyEmployeeDetail = () =>
  import('@/pages/hr/EmployeeDetailPage').then((m) => ({ Component: m.EmployeeDetailPage }));
const lazyMyEmployeeProfile = () =>
  import('@/pages/hr/MyEmployeeProfilePage').then((m) => ({ Component: m.MyEmployeeProfilePage }));
const lazyTicketsList = () =>
  import('@/pages/tickets/TicketsListPage').then((m) => ({ Component: m.TicketsListPage }));
const lazyTicketDetail = () =>
  import('@/pages/tickets/TicketDetailPage').then((m) => ({ Component: m.TicketDetailPage }));
const lazyConversationsList = () =>
  import('@/pages/ai/ConversationsListPage').then((m) => ({ Component: m.ConversationsListPage }));
const lazyConversationDetail = () =>
  import('@/pages/ai/ConversationDetailPage').then((m) => ({ Component: m.ConversationDetailPage }));
const lazyKnowledgeBase = () =>
  import('@/pages/kb/KnowledgeBasePage').then((m) => ({ Component: m.KnowledgeBasePage }));

export const router = createBrowserRouter([
  {
    element: <RedirectIfAuthenticated />,
    children: [
      {
        element: <AuthLayout />,
        children: [
          { path: paths.login, lazy: lazyLogin },
          { path: paths.register, lazy: lazyRegister },
          { path: paths.forgotPassword, lazy: lazyForgot },
          { path: paths.resetPassword, lazy: lazyReset },
        ],
      },
    ],
  },
  {
    element: <RequireAuth />,
    children: [
      {
        element: <AppLayout />,
        children: [
          { index: true, lazy: lazyDashboard },
          { path: paths.profile, lazy: lazyProfile },
          { path: paths.tickets, lazy: lazyTicketsList },
          { path: `${paths.tickets}/:id`, lazy: lazyTicketDetail },
          { path: paths.aiConversations, lazy: lazyConversationsList },
          { path: `${paths.aiConversations}/:id`, lazy: lazyConversationDetail },
          {
            element: (
              <RequireAbility
                abilities={['products.view', 'categories.view', 'suppliers.view', 'inventory.view']}
              />
            ),
            children: [{ path: paths.inventory, lazy: lazyInventory }],
          },
          { path: paths.myEmployeeProfile, lazy: lazyMyEmployeeProfile },
          {
            element: <RequireAbility ability="employees.view" />,
            children: [
              { path: paths.employees, lazy: lazyEmployeesList },
              { path: `${paths.employees}/:id`, lazy: lazyEmployeeDetail },
            ],
          },
          {
            element: <RequireAbility abilities={['departments.view', 'positions.view']} />,
            children: [{ path: paths.departments, lazy: lazyHR }],
          },
          {
            element: <RequireAbility ability="knowledge_base.view" />,
            children: [
              { path: paths.knowledgeBase, lazy: lazyKnowledgeBase },
            ],
          },
          {
            element: <RequireAbility ability="automation.view" />,
            children: [{ path: paths.automation, element: <ComingSoonPage title="Automation" /> }],
          },
          { path: paths.auditLog, element: <ComingSoonPage title="Audit Log" /> },
          { path: paths.forbidden, element: <ForbiddenPage /> },
        ],
      },
    ],
  },
  { path: '*', element: <NotFoundPage /> },
]);
