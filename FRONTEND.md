# React Frontend Design

Status: Draft v1.0 (design phase, no code written yet)
Related: [ARCHITECTURE.md](ARCHITECTURE.md) §6 · [API.md](API.md) · [BACKEND.md](BACKEND.md)

Confirmed stack — this supersedes the earlier Next.js proposal in
ARCHITECTURE.md §6:

| Concern | Choice | Why |
|---|---|---|
| Build tool | Vite | Fast dev server/HMR, no need for SSR given this is an authenticated internal business app, not a public marketing site |
| UI | React 18+, TypeScript strict (no `any`) | Matches project rules |
| Routing | React Router (data router: `createBrowserRouter`) | Explicit route objects, built-in loaders/actions if needed later, nested layouts via `<Outlet/>` |
| Server state | TanStack Query | Cache, retries, background refetch, pagination helpers |
| Styling | Tailwind CSS | Utility-first, pairs with a small token layer for theming (§10) |
| Global client state | Zustand (minimal) | Only for auth session, active tenant, UI prefs — everything else lives in Query's cache |
| Forms | React Hook Form + Zod | Schema validation mirrors backend Form Requests (BACKEND.md §11) |
| Tables | TanStack Table | Headless, composes with the server-side cursor pagination from API.md §3.2 |
| Charts | Recharts | Composable SVG charts, good TypeScript support |
| Headless primitives | Radix UI | Accessible unstyled Dialog/Popover/Menu/Tabs underneath `components/ui` |
| Types | Generated from OpenAPI (`docs/api/openapi.yaml`, API.md §5) | Frontend/backend never drift |
| Testing | Vitest + Testing Library, Playwright for E2E | Per ARCHITECTURE.md §17 |

## 1. Folder Structure

```
frontend/
├── index.html
├── vite.config.ts
├── tailwind.config.ts
├── src/
│   ├── main.tsx                      # ReactDOM.createRoot, wraps <App/> in providers
│   ├── App.tsx                       # QueryClientProvider, RouterProvider, ThemeProvider
│   │
│   ├── routes/
│   │   ├── router.tsx                # createBrowserRouter route tree (§2, §3)
│   │   ├── routes.config.ts          # path constants + per-route required ability
│   │   └── guards/
│   │       ├── RequireAuth.tsx       # redirects to /login if unauthenticated
│   │       └── RequireAbility.tsx    # redirects to /403 if missing permission (§9)
│   │
│   ├── pages/                        # route-level components only — compose modules, no business logic
│   │   ├── auth/
│   │   ├── dashboard/
│   │   ├── hr/
│   │   ├── inventory/
│   │   ├── sales/
│   │   ├── tickets/
│   │   ├── ai/
│   │   ├── automation/
│   │   ├── settings/
│   │   └── errors/
│   │
│   ├── layouts/
│   │   ├── AuthLayout.tsx            # centered card, no nav
│   │   ├── AppLayout.tsx             # sidebar + topbar + <Outlet/>
│   │   └── SettingsLayout.tsx        # nested tab layout within AppLayout
│   │
│   ├── modules/<domain>/             # vertical slices — same 9 domains as BACKEND.md
│   │   ├── components/               # feature components (data-aware)
│   │   ├── hooks/                    # TanStack Query hooks for this domain (§6)
│   │   ├── services/                 # typed API functions (§7)
│   │   ├── forms/                    # RHF + Zod schemas (§8)
│   │   └── types/                    # domain-specific types not covered by generated types
│   │
│   ├── components/
│   │   ├── ui/                       # design-system primitives: Button, Input, Select, Dialog, Badge, Toast...
│   │   ├── data-table/               # generic <DataTable/> built on TanStack Table (§9)
│   │   ├── charts/                   # chart wrapper components (§10)
│   │   └── layout/                   # Sidebar, Topbar, PageHeader, EmptyState, ErrorState
│   │
│   ├── hooks/                        # cross-domain hooks: useAuth, useTenant, useAbility, useMediaQuery, useDebouncedValue
│   ├── lib/
│   │   ├── api-client.ts             # typed fetch wrapper, auth/tenant headers, 401 refresh, error parsing
│   │   ├── query-client.ts           # QueryClient instance + default options
│   │   ├── config.ts                 # env-derived runtime config
│   │   └── errors.ts                 # ApiError type + helpers matching API.md §3.3
│   ├── store/                        # Zustand: authStore, uiStore
│   ├── theme/                        # design tokens, ThemeProvider, useTheme (§11)
│   ├── types/                        # generated OpenAPI types (`api.generated.ts`) + shared hand-written types
│   └── tests/
├── public/
└── e2e/                              # Playwright specs
```

Each `modules/<domain>` is self-contained: its components, hooks,
services, and forms live together. Cross-domain sharing only happens
through `components/ui`, `components/data-table`, `components/charts`,
`hooks/`, `lib/`, and `types/` — the same discipline ARCHITECTURE.md §6
already established, now made concrete.

## 2. Pages

One page component per route — composes layout + module components +
data hooks, contains no business logic itself. Domain codes match
BACKEND.md's legend.

| Domain | Page | Route | Notes |
|---|---|---|---|
| — | `LoginPage` | `/login` | |
| — | `ForgotPasswordPage` | `/forgot-password` | |
| — | `ResetPasswordPage` | `/reset-password` | |
| — | `AcceptInvitePage` | `/invite/:token` | Accepts a `tenant_users` invitation |
| — | `DashboardPage` | `/` | Cross-domain charts/widgets (§10) |
| RBAC | `TenantSettingsPage` | `/settings` | |
| RBAC | `MembersPage` | `/settings/members` | `tenant_users` list + invite |
| RBAC | `RolesPage` | `/settings/roles` | Role + permission assignment |
| RBAC | `ApiTokensPage` | `/settings/tokens` | `personal_access_tokens` |
| HR | `DepartmentsPage` | `/hr/departments` | |
| HR | `DepartmentDetailPage` | `/hr/departments/:id` | |
| HR | `EmployeesPage` | `/hr/employees` | |
| HR | `EmployeeDetailPage` | `/hr/employees/:id` | |
| INV | `SuppliersPage` | `/inventory/suppliers` | |
| INV | `ProductsPage` | `/inventory/products` | |
| INV | `ProductDetailPage` | `/inventory/products/:id` | |
| INV | `WarehousesPage` | `/inventory/warehouses` | |
| INV | `InventoryLevelsPage` | `/inventory/levels` | `inventory_items` + reorder alerts |
| INV | `PurchaseOrdersPage` | `/inventory/purchase-orders` | |
| INV | `PurchaseOrderDetailPage` | `/inventory/purchase-orders/:id` | submit/receive/cancel actions |
| SALES | `CustomersPage` | `/sales/customers` | |
| SALES | `CustomerDetailPage` | `/sales/customers/:id` | |
| SALES | `SalesOrdersPage` | `/sales/orders` | |
| SALES | `SalesOrderDetailPage` | `/sales/orders/:id` | confirm/fulfill/cancel actions |
| SALES | `PaymentsPage` | `/sales/payments` | read + refund action |
| TICK | `TicketsPage` | `/tickets` | Queue view, filterable by status/assignee |
| TICK | `TicketDetailPage` | `/tickets/:id` | Thread: comments + attachments |
| TICK | `TicketCategoriesPage` | `/tickets/categories` | Admin-only |
| AI | `ConversationsPage` | `/ai/conversations` | |
| AI | `ConversationDetailPage` | `/ai/conversations/:id` | Chat UI, SSE streaming |
| AI | `KnowledgeBasePage` | `/knowledge-base` | |
| AI | `ArticleDetailPage` | `/knowledge-base/:id` | |
| AI | `ArticleEditorPage` | `/knowledge-base/:id/edit` | publish/archive actions |
| AUTO | `WorkflowsPage` | `/automation/workflows` | |
| AUTO | `WorkflowBuilderPage` | `/automation/workflows/:id` | Trigger/condition/action step editor |
| AUTO | `AutomationJobsPage` | `/automation/jobs` | Execution history + retry |
| AUTO | `IntegrationsPage` | `/automation/integrations` | OAuth connect/disconnect |
| AUDIT | `AuditLogPage` | `/settings/audit-log` | Admin-only |
| AUDIT | `NotificationsPage` | `/notifications` | Full list; a dropdown preview also lives in `Topbar` |
| — | `ForbiddenPage` | `/403` | |
| — | `NotFoundPage` | `*` | |

## 3. Routing & Layouts

React Router's data router with nested route objects — layouts are routes
that render `<Outlet/>`, not wrapper components manually included per page.

```
/                          → RequireAuth → AppLayout
  index                    → DashboardPage
  hr/departments           → DepartmentsPage
  hr/departments/:id       → DepartmentDetailPage
  ...
  settings                 → RequireAbility('settings.view') → SettingsLayout
    index                  → TenantSettingsPage
    members                → MembersPage
    roles                  → RolesPage
    tokens                 → ApiTokensPage
    audit-log              → RequireAbility('audit.view') → AuditLogPage
/login, /forgot-password   → AuthLayout (public)
/invite/:token             → AuthLayout (public)
/403, *                    → no layout (standalone error pages)
```

- **`AuthLayout`** — centered card, no navigation; wraps every
  unauthenticated route.
- **`AppLayout`** — persistent `Sidebar` (domain navigation) +
  `Topbar` (tenant switcher, notifications bell, user menu) + `<Outlet/>`
  for the active page. Collapses per §12.
- **`SettingsLayout`** — nested tab navigation within `AppLayout`, only
  reachable through it, matching the RBAC-gated admin routes.
- **Guards**: `RequireAuth` wraps the entire authenticated tree once;
  `RequireAbility(permission)` wraps individual admin-only branches,
  mirroring the backend's `CheckAbility` middleware and Policies
  (BACKEND.md §9, §4) — a route a user can't reach here is also a route
  their token can't call.
- **Code splitting**: every `pages/*` component is `React.lazy`-loaded per
  route, so the initial bundle only includes the shell + the active page.

## 4. Components

Three tiers, each with a distinct rule about what it's allowed to know:

| Tier | Location | Allowed to | Not allowed to |
|---|---|---|---|
| Primitives | `components/ui/` | Accept props, render Radix-based markup, be fully reusable/dumb | Fetch data, know about any domain |
| App chrome | `components/layout/` | Read cross-cutting state (`useAuth`, `useTenant`, notification count) | Contain domain business logic |
| Feature components | `modules/<domain>/components/` | Call that domain's hooks (§6), compose primitives | Be imported by another domain — cross-domain reuse only happens by promoting a component up to `components/ui` |

Compound-component pattern (`Dialog.Root` / `Dialog.Trigger` /
`Dialog.Content`) is used for anything with internal open/close or
multi-part state, matching Radix's own API shape so primitives don't
fight the library underneath them. Naming: `PascalCase` component files,
one component per file, colocated `*.test.tsx`.

## 5. Theme

- **Design tokens**: colors, spacing, radii, and typography defined once
  as CSS custom properties (`--color-primary`, `--color-surface`,
  `--radius-md`, ...) in `theme/tokens.css`, consumed by `tailwind.config.ts`
  via `theme.extend.colors` referencing `var(--color-*)` — Tailwind
  utility classes stay the only styling API used in components; raw CSS
  values are never hardcoded in a component file.
- **Light/dark mode**: class-based strategy (`class="dark"` on `<html>`),
  defaulting to `prefers-color-scheme`, overridable and persisted via
  `uiStore` (Zustand, `localStorage`-backed). `ThemeProvider` sets the
  class; `useTheme()` exposes `{ theme, setTheme }` to any component
  (used once, in the user menu).
- **Tenant branding** (enterprise plans): a tenant's `settings.branding`
  (primary color, logo URL — see `tenants.settings` in DATABASE.md §4.1)
  overrides the relevant CSS variables at runtime after login, so the
  same Tailwind utility classes automatically reflect tenant branding
  without per-component conditional styling.
- **Components never hardcode color values** — only token-backed Tailwind
  classes (`bg-surface`, `text-primary`), so dark mode and tenant
  branding both work for free everywhere.

## 6. Hooks

Four categories, all under `hooks/` (cross-domain) or
`modules/<domain>/hooks/` (domain-scoped):

| Category | Examples | Notes |
|---|---|---|
| Data (TanStack Query) | `useProducts()`, `useProduct(id)`, `useCreateProduct()`, `useUpdateSalesOrder(id)` | Naming: `use{Entities}` (list), `use{Entity}(id)` (detail), `use{Verb}{Entity}` (mutation). Every one wraps a function from that domain's `services/` (§7) — hooks never call `fetch` directly. |
| Domain/business | `useInventoryReorderAlerts()`, `useTicketSlaCountdown(ticket)`, `useWorkflowStepValidation()` | Derived/composed logic specific to one domain, still built on top of the data hooks above |
| UI | `useDisclosure()`, `useMediaQuery(query)`, `useDebouncedValue(value, ms)`, `useCursorPagination()` | Stateless, reusable anywhere, no API awareness |
| Cross-cutting | `useAuth()`, `useTenant()`, `useAbility(permission)`, `useIdempotencyKey()` | Backed by `authStore`/`uiStore`; `useAbility` is the client-side mirror of the backend's RBAC check (BACKEND.md §4) used both for route guards (§3) and for hiding/disabling individual buttons |

Every list-data hook accepts the same shape of params
(`{ filter, sort, cursor }`) mirroring API.md §4's query conventions, so
`DataTable` (§9) can drive any domain's list hook identically.

## 7. Services

`lib/api-client.ts` is the single low-level HTTP layer: attaches the
auth token and `X-Tenant-Id`/subdomain context, attaches `Idempotency-Key`
where a call requests one, retries a 401 once after a token refresh, and
parses every response into either the typed `data` payload or a typed
`ApiError` matching API.md §3.3 — no component or hook ever touches
`fetch` directly.

`modules/<domain>/services/*.ts` are thin, typed wrappers per aggregate
root, one file per BACKEND.md controller (e.g. `products.ts` exposes
`listProducts`, `getProduct`, `createProduct`, `updateProduct`,
`deleteProduct`), built on top of types generated from
`docs/api/openapi.yaml` (API.md §5) — so a backend response-shape change
fails the frontend typecheck instead of surfacing as a runtime bug.

This services layer is the frontend's equivalent of the backend's
Repository layer (BACKEND.md §3): the only place that knows an endpoint
URL exists. Query hooks (§6) are the equivalent of the Service layer:
where retry/cache/derived-state behavior lives.

## 8. Forms

React Hook Form + Zod, colocated per domain in `modules/<domain>/forms/`,
one schema per write operation — schema names deliberately mirror the
backend's Form Request names (BACKEND.md §11) so the two stay conceptually
paired even though they're validated independently on each side
(`createProductSchema` ↔ `StoreProductRequest`,
`assignTicketSchema` ↔ `AssignTicketRequest`).

- **Reusable primitives**: `FormField`, `FormError`, `FormSection` in
  `components/ui/form/` wrap RHF's `Controller` around the `ui`
  primitives (`Input`, `Select`, ...), so every form gets consistent
  label/error/help-text layout for free.
- **Server error mapping**: a shared `applyApiErrorsToForm(error, setError)`
  helper walks the API error envelope's `error.details[]` (API.md §3.3)
  and calls RHF's `setError(field, ...)` per entry, so backend validation
  failures surface inline on the same fields as client-side Zod errors.
- **Optimistic vs. pessimistic**: low-risk, easily-reversible mutations
  (marking a notification read, toggling a draft workflow's name) update
  the TanStack Query cache optimistically; anything with real-world side
  effects (sales order confirmation, payment refund, workflow activation)
  waits for the server response before updating the UI.
- **Multi-step forms** (e.g. `WorkflowBuilderPage`'s step editor,
  `PurchaseOrderDetailPage`'s line items) manage step state with RHF's
  `useFieldArray` rather than hand-rolled array state.

## 9. Tables

A single generic `<DataTable/>` (`components/data-table/`) built on
TanStack Table, parameterized per domain by column defs — not a bespoke
table component per page.

- **Server-side everything**: sorting, filtering, and pagination are not
  done client-side against a full dataset. `DataTable` emits
  `{ sort, filter, cursor }` changes, which the calling page's data hook
  (§6) turns into query params matching API.md §4 (`?sort=-created_at&filter[status]=open`)
  and §3.2 (cursor-based `meta.pagination`).
- **Column defs** live with each domain (`modules/<domain>/components/*Columns.tsx`),
  each declaring its own cell renderer (status badges, currency
  formatting, relative timestamps) — `DataTable` itself has no
  domain-specific formatting logic.
- **Row actions**: a per-row overflow menu driven by `useAbility` (§6) —
  an action the user's role can't perform is never rendered, not just
  disabled.
- **Responsive fallback**: below the `md` breakpoint, `DataTable` renders
  the same rows as a stacked card list instead of a horizontally-scrolled
  table (§12) — one component, two render paths, controlled by
  `useMediaQuery`.

## 10. Charts

`components/charts/` wraps Recharts in a small set of pre-themed chart
components (`LineChart`, `BarChart`, `DonutChart`, `Sparkline`), each
pulling its color series from the same design tokens as §5 rather than
hardcoded hex values, so charts stay correct in dark mode and under
tenant branding automatically.

Primary consumers:

| Page | Chart | Data source |
|---|---|---|
| `DashboardPage` | Sales trend (`LineChart`) | Sales aggregate endpoint |
| `DashboardPage` | Ticket volume by status (`BarChart`) | Tickets aggregate endpoint |
| `DashboardPage` | Inventory health (`DonutChart`: healthy/low/out-of-stock) | Inventory aggregate endpoint |
| `AutomationJobsPage` | Job success/failure rate (`Sparkline` per workflow) | Automation jobs aggregate endpoint |

**Open item**: BACKEND.md's route catalogue currently only defines
CRUD/list endpoints, not aggregate/reporting endpoints — these charts
need dedicated `GET /v1/reports/*` endpoints (e.g.
`/v1/reports/sales-trend`, `/v1/reports/ticket-volume`) added to
`API.md`/`BACKEND.md` before implementation; not designed yet here to
avoid guessing an aggregation shape the backend hasn't committed to.

## 11. State Management Summary

| State | Lives in | Why |
|---|---|---|
| Any data from the API | TanStack Query cache | It's a cache of server truth, not client-owned state — refetching beats hand-synced duplication |
| Auth session, active tenant | `authStore` (Zustand) | Needed before any query can run; not itself server data |
| Theme, sidebar collapsed, active filters-panel-open | `uiStore` (Zustand) | Pure UI preference, often `localStorage`-persisted |
| Form field values, validation state | React Hook Form | Local to the form's lifecycle |
| Everything else | Local component `useState` | Default choice; only promoted to a store when two unrelated components genuinely need it |

## 12. Responsive Design

Tailwind's default breakpoints, mobile-first (`base` styles target
mobile, `md:`/`lg:` add up):

| Breakpoint | Width | Layout behavior |
|---|---|---|
| base | < 640px | `AppLayout` sidebar becomes a slide-over drawer opened from the `Topbar` hamburger; `DataTable` renders as stacked cards; forms are always single-column; dialogs render as full-screen sheets |
| `sm` | ≥ 640px | Dialogs regain fixed max-width/centered presentation |
| `md` | ≥ 768px | `DataTable` switches to the tabular layout; two-column forms where fields are genuinely related (e.g. first/last name) |
| `lg` | ≥ 1024px | `AppLayout` sidebar becomes persistent/pinned; dashboard charts move from a stacked to a grid layout |
| `xl` | ≥ 1280px | Detail pages (e.g. `SalesOrderDetailPage`) split into a two-pane layout (record + side panel of related activity/comments) |

- `useMediaQuery` (§6) is the single source of truth for
  breakpoint-driven behavior in components that need to render
  *differently* (not just resize) — CSS handles pure visual scaling via
  Tailwind responsive classes; JS is only involved when the component
  tree itself changes (table ↔ cards, sidebar drawer ↔ pinned).
- Touch targets follow a 44px minimum on `base`, enforced through the
  `ui` primitives' size variants rather than per-usage overrides.
- `ConversationDetailPage`'s chat layout and `WorkflowBuilderPage`'s step
  editor are the two views explicitly designed desktop-first with a
  reduced (view/comment-only, no editing) mobile mode, since building a
  full drag-and-drop workflow step editor for a small viewport isn't a
  reasonable mobile use case.

## 13. Open Decisions

- Reporting/aggregate API endpoints backing §10's charts don't exist yet
  in BACKEND.md — need to be designed and added before chart data-fetching
  hooks can be implemented.
- Whether `ConversationDetailPage`'s chat view gets an optional
  PWA-style "app-like" mobile mode later, given it's one of the more
  mobile-plausible pages despite the general desktop-first admin-tool
  framing of the rest of the product.
