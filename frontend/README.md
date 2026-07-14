# AI Business Platform — Frontend

Vite + React 18 + TypeScript (strict) SPA for the Laravel API in `../backend`.
Stack: React Router (data router), TanStack Query, Tailwind + shadcn/ui
(Radix), Zustand, React Hook Form + Zod. See [`../FRONTEND.md`](../FRONTEND.md)
for the full design.

> **Status:** Slice 1 (**Foundation + Auth**), Slice 2 (**Inventory**), and
> Slice 3 (**Employee/HR**) are done. Inventory covers Categories, Products,
> Suppliers, and Stock (levels/adjust/movements) as one tabbed page — there is
> no Warehouses or Purchase Orders UI, since those don't exist in the backend;
> stock is tracked per product only. Employee/HR covers Departments +
> Positions (one tabbed page), a full Employees list/detail (with avatar
> upload and notes), and a self-service "My Profile" view — there is no Leave
> Request workflow, since `on_leave` is just a manually-set status with no
> backend support behind it. Every other module's nav item still routes to a
> "coming soon" placeholder and will be filled in one slice at a time.

## Prerequisites

- Node 20+ and npm
- The backend running and migrated/seeded:
  ```bash
  cd ../backend
  php artisan migrate --seed
  php artisan serve            # http://127.0.0.1:8000
  ```

## Run

```bash
npm install
cp .env.example .env           # optional — sensible defaults are built in
npm run dev                    # http://localhost:5173
```

In dev the SPA calls same-origin `/api/*`, which Vite proxies to the backend
(`VITE_API_PROXY_TARGET`, default `http://127.0.0.1:8000`) — so no backend CORS
config is needed yet. Production hosting will need CORS or same-origin serving.

## Scripts

| Command | What it does |
|---|---|
| `npm run dev` | Vite dev server with HMR |
| `npm run build` | Type-check (`tsc -b`) + production build |
| `npm run test` | Vitest (jsdom + Testing Library) |
| `npm run lint` | ESLint (strict; `no-explicit-any`) |
| `npm run typecheck` | Type-check without emit |

## Auth / API notes

- Token-based Sanctum: login/register return `data.token` (bearer) which is
  stored in `authStore` (persisted) and sent as `Authorization: Bearer`. The
  tenant is baked into the token — no tenant header is sent.
- There is no refresh endpoint; a `401` clears the session and redirects to
  `/login`.
- Multi-tenant accounts: login may return `409` with `available_tenants`; the
  login form reveals an organization picker and resubmits with `tenant_slug`.
- Server validation errors (`error.details[]`) are mapped back onto form fields
  via `applyApiErrorsToForm`.

## Layout

```
src/
  lib/         api-client, errors, query-client, cn, apply-api-errors, config
  store/       authStore, uiStore (Zustand, persisted)
  theme/       tokens.css, ThemeProvider, useTheme
  components/  ui/ (shadcn primitives), layout/ (Sidebar, Topbar, ...)
  hooks/       useAuth, useAbility, useTenant
  routes/      router, routes.config, guards/
  layouts/     AuthLayout, AppLayout
  modules/     auth/ (services, hooks, forms, components, types)
               inventory/ (services, hooks, forms, components, types)
               employee/ (services, hooks, forms, components, types)
  pages/       auth/, settings/, errors/, inventory/, hr/, DashboardPage, ComingSoonPage
  tests/       setup, fixtures
```

## Employee/HR notes

- Departments + Positions share one tabbed page (`/hr/departments`), each
  gated by its own ability, same pattern as Inventory. Employees gets its own
  list (`/hr/employees`, with filters/search/sort — the only list endpoint in
  this whole API that supports `sort`/`direction`/a client-adjustable
  `per_page`) and a detail route (`/hr/employees/:id`) with avatar upload and
  notes, since a dialog didn't have room for that much content.
- **Self-service edit restriction**: the backend rejects a `PATCH
  /employees/{id}` from an employee editing their own record (no
  `employees.manage`) if any of department/position/manager/employment
  type/status/hire or termination date differ from their stored value — even
  by omission. `EmployeeFormFields` handles this by simply not rendering
  inputs for those fields when the actor can't manage employees; their
  current values still round-trip in the submit payload because
  react-hook-form keeps a field's value after `reset()` even while its input
  is unmounted (`shouldUnregister` defaults to `false`).
- **My Profile** (`/hr/my-profile`, backed by `GET/PATCH /employees/me`) is a
  self-service view distinct from the account-level Settings > Profile — a
  404 there is a normal "no linked employee record" outcome, not an error.
- No Leave Request workflow exists — `employment_status: 'on_leave'` is just a
  manually-settable enum value with no dates or approval flow behind it.
- Manager/Department/Position pickers only fetch the first page (Employees
  allows up to `per_page=100`; Departments/Positions are hardcoded to 25) —
  large tenants may not see every option in these selects.

## Inventory notes

- Categories/Products/Suppliers/Stock share one tabbed page (`/inventory`),
  each tab independently gated by its own ability (`categories.view`,
  `products.view`, `suppliers.view`, `inventory.view`) so a role with only one
  of them still sees that tab.
- List endpoints are cursor-paginated with a server-fixed `per_page=25` and no
  generic `sort` param — the shared `DataTable` (`components/data-table/`)
  only renders server order and drives Prev/Next off `meta.pagination`.
- Stock adjustment (`POST /stock/{product}/adjust`) takes a signed `quantity`
  (positive for inbound, negative for outbound, either sign for a manual
  adjustment) — enforced client-side by `adjustStockSchema`.
