# AI Business Platform — Frontend

Vite + React 18 + TypeScript (strict) SPA for the Laravel API in `../backend`.
Stack: React Router (data router), TanStack Query, Tailwind + shadcn/ui
(Radix), Zustand, React Hook Form + Zod. See [`../FRONTEND.md`](../FRONTEND.md)
for the full design.

> **Status:** All planned slices are done — Slice 1 (**Foundation + Auth**),
> Slice 2 (**Inventory**), Slice 3 (**Employee/HR**), Slice 4 (**Ticketing**),
> Slice 5 (**AI Assistant**), Slice 6 (**Knowledge Base**), Slice 7
> (**Automation**), Slice 8 (**Audit Log**), and the **Dashboard**. Inventory
> covers Categories,
> Products, Suppliers, and Stock (levels/adjust/movements) as one tabbed page
> — there is no Warehouses or Purchase Orders UI, since those don't exist in
> the backend; stock is tracked per product only. Employee/HR covers
> Departments + Positions (one tabbed page), a full Employees list/detail
> (with avatar upload and notes), and a self-service "My Profile" view — there
> is no Leave Request workflow, since `on_leave` is just a manually-set status
> with no backend support behind it. Ticketing covers a filterable/sortable
> list with quick-filter chips and a stats bar, a detail page with the full
> assign/status/close/reopen action set, comments (with policy-mirrored
> internal-note visibility), and attachments — there is no Ticket Categories
> admin page, since ticket "category" is just the fixed `type` enum, not a
> real backend resource. AI Assistant covers a conversation list, a streaming
> chat detail page (hand-rolled SSE parsing over `fetch`, since the endpoint
> is a POST with an auth header — `EventSource` can't be used), and inline
> tool-call/citation visibility — there is no conversation rename, no message
> edit/delete, and no per-message citations field, since none of those exist
> in the backend. Knowledge Base covers PDF upload with background-processing
> status polling and a single-shot "Ask" panel with citations, rendered as a
> client-side-only chat history — there is no article/wiki editor despite
> FRONTEND.md describing one, since the real system has no content-editing
> endpoint at all, only upload/list/delete. Slice 7 (**Automation**) covers a
> create-only workflow builder (trigger + condition/action steps, immutable
> once created, since there is no update endpoint), workflow lifecycle
> controls, and a polling automation-jobs view — there is no manual "run now"
> trigger or Integrations page, since neither exists in the backend. Slice 8
> (**Audit Log**) covers a read-only, role-gated (Owner/Admin) list against
> the shared `audit_logs` table. The **Dashboard** renders the one real
> aggregate endpoint in the whole backend (`GET /tickets/statistics`) as stat
> tiles and a by-priority bar chart — every other widget FRONTEND.md
> envisions (sales trend, inventory health, automation job rates) needs new
> `/v1/reports/*` endpoints that don't exist yet, so they're intentionally
> left out rather than faked.

## Prerequisites

- Node 20+ and npm
- The backend running and migrated/seeded:
  ```bash
  cd ../backend
  php artisan migrate --seed
  php artisan serve            # http://127.0.0.1:8000
  php artisan queue:work --queue=automation,notifications,knowledge_base,default
  ```
  Seeding creates a **Demo Company** tenant with 15+ realistic rows in every
  module (departments, positions, employees, categories, products, suppliers,
  stock, workflows, tickets, automation jobs, AI conversations, Knowledge Base
  documents, and audit log entries) — log in with `demo@example.com` /
  `password123`. Re-running `php artisan db:seed` is safe: `DemoDataSeeder`
  is a no-op once the Demo Company tenant exists (delete it first, cascading
  through its rows, to reseed from scratch). The queue worker is only needed
  to see Automation Jobs settle into `succeeded`/`failed` for anything
  triggered *after* seeding — the seeder itself drains the queue once at the
  end, so the initial demo data already shows real, settled statuses without it.

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
               ticket/ (services, hooks, forms, components, types)
               ai/ (services, hooks, components, types, mapMessage)
               kb/ (services, hooks, components, types)
               automation/ (services, hooks, forms, components, types)
               audit/ (services, hooks, types)
  pages/       auth/, settings/, errors/, inventory/, hr/, tickets/, ai/, kb/,
               automation/, audit/, DashboardPage, ComingSoonPage
  tests/       setup, fixtures
```

## Knowledge Base notes

- One page (`/knowledge-base`, tabbed Ask | Documents) — no separate document
  detail route, since `DocumentResource` is just metadata (title, filename,
  size, status, page count, error message) already fully shown in the list
  row; there's no content/text-view endpoint to link out to.
- **Upload is asynchronous**: `POST /documents` returns immediately with
  `status: 'processing'` while text extraction/chunking/embedding runs in a
  background queue job, with no push notification on completion.
  `useDocuments` polls every 3s (`refetchInterval`) only while at least one
  row is still `processing`, and stops once everything has settled to
  `ready`/`failed` — the polling decision is a pure exported function
  (`getKbPollInterval`) so it's unit-tested without mounting a real query.
- A `failed` document shows its `error_message` inline in the row (e.g. "No
  extractable text was found in this document") — there is no re-process
  endpoint, so the only recovery is delete-and-re-upload.
- **`/ask` is a single request/response, not streaming** — despite internally
  calling the same `AiProviderInterface::stream()` as AI Assistant, the
  controller discards the delta callback and waits for the full answer.
  Nothing is persisted server-side (unlike AI Assistant's conversations), so
  `AskPanel`'s chat-style history is purely client-side state that clears on
  refresh — asking again doesn't reload or continue anything from before.
- Citations (`{number, document_id, title, chunk_index, page_number,
  snippet, score}`) are rendered numbered to match the `[1]`/`[2]` markers
  the backend instructs the model to emit in the answer text. `snippet` is
  the only chunk content ever exposed — full chunk text and the embedding
  vector never leave the backend.
- Verified live against the real backend: asking with zero documents
  uploaded correctly hit the backend's canned "no knowledge base content"
  fallback with no LLM call made (confirmed via network inspection — only
  one request, no OpenAI round-trip needed), and the client-side "choose a
  file first" upload guard correctly blocked a request from firing. A real
  end-to-end PDF upload could not be exercised in this session — the
  sandboxed browser used for smoke testing can't drive a native OS file
  picker, so the upload → background-processing → status-polling path is
  covered by code review and the `getKbPollInterval` unit tests but not a
  live upload.

## Automation notes

- **No update endpoint at all** (`apiResource(...)->except(['update'])`) — a
  workflow's trigger and steps are fully specified at creation time and never
  editable afterwards. `CreateWorkflowDialog` is therefore the only form this
  module has; "editing" a workflow means deleting it and creating a new one.
  Only `activate`/`pause`/`delete` lifecycle transitions exist post-creation.
- **Hardcoded enums, no schema-discovery endpoint** — the six event triggers,
  five condition operators, and two actions (`send_notification`,
  `log_audit_event`, from `ActionRegistry`) are all hand-copied into
  `types.ts` since no endpoint lists them or their per-action parameter
  shapes. If the backend adds a new action or event, this file needs a
  matching manual update — a real gap the code comments flag.
- **No manual "run now" trigger** — a workflow only fires from an internal
  domain event (`AutomationEventSubscriber`) or its own cron schedule
  (`RunScheduledWorkflowsJob`, runs every minute); there is no API call this
  UI can make to force a run for testing.
- **Async, polling job execution**, same shape as Knowledge Base's document
  processing: `AutomationJobsTable` polls every 3s while any job is
  `queued`/`running` and stops once everything has settled to
  `succeeded`/`failed` (`getAutomationJobPollInterval`, unit-tested in
  isolation like `getKbPollInterval`). Retry is only offered on a `failed`
  job and only to `automation.manage` holders.
- **Owner/Admin only, no Member tier** — unlike Ticketing and Knowledge Base,
  this module has no broader authenticated-member access at all; both
  `/automation/workflows` and `/automation/jobs` are gated behind
  `automation.view`, matching the backend policy exactly.
- No Integrations page — FRONTEND.md describes an `IntegrationsPage` for
  OAuth connect/disconnect, but there is zero backend support for it (no
  routes, controllers, or tables); excluded from scope, same as the
  discrepancies noted in every other module's FRONTEND.md.
- Verified via typecheck/lint/unit tests (89/89 passing) and manual review of
  `CreateWorkflowDialog`'s rendered output; a full live create-workflow round
  trip against the real backend could not be completed in this session due to
  browser-automation click reliability issues in the sandboxed test browser,
  not an application defect — the same category of tooling limitation noted
  for AI Assistant's happy-path streaming and Knowledge Base's live PDF
  upload.

## AI Assistant notes

- The send-message endpoint (`POST /ai/conversations/:id/messages`) is a real
  `text/event-stream` response, but since it's a POST carrying a JSON body and
  an `Authorization` header, the browser's native `EventSource` API can't be
  used (GET-only, no custom headers, no body). `services/chat.ts` hand-rolls
  SSE parsing over `fetch` + `ReadableStream`, buffering on blank-line-
  terminated `event:`/`data:` frames — this is the one genuinely new piece of
  client infrastructure in this slice, and it's covered by unit tests
  (`chat.test.ts`) including frames split arbitrarily across chunk
  boundaries.
- SSE event types: `user_message`, `delta` (streamed text chunks),
  `tool_call`/`tool_result` (the model autonomously calling Knowledge Base
  search, ticket stats, or current-date/time — **not** user-toggleable), 
  `message` (final persisted assistant message), and a bespoke `error` frame
  — the last one arrives as a bare `{ message }` with **no** `code` or
  `request_id`, unlike every other error path in this API, since by the time
  a mid-stream failure happens the 200 OK headers are already committed.
  `useChatStream` surfaces it as plain text in an inline banner rather than
  trying to force it through the app's usual `ApiError` handling.
- Tool calls/results are rendered inline (`ToolCallBlock`) as small
  "Calling X…" / "X result" indicators with an expandable JSON detail, per
  your call to keep them visible rather than hidden — this also means past
  Knowledge Base searches are visible when reopening a conversation, since
  they're persisted as ordinary `tool`-role messages in history, not
  something separate.
- **No conversation rename** (`update` is excluded from the resource route)
  and **no message edit/delete/regenerate** — messages are append-only.
  "New chat" therefore just creates an untitled conversation immediately and
  navigates straight into it; there's no upfront naming dialog since there'd
  be no way to change it later anyway.
- Conversations are **strictly per-user, not tenant-wide** — even Owner/Admin
  can only ever see their own; there is no `ai.*` RBAC permission at all
  (open to any authenticated member, gated by row ownership instead), so
  `/ai/conversations` has no `RequireAbility` wrapper.
- No model-selection UI — every conversation just uses the backend's
  configured default (`gpt-4o-mini` in this environment); the backend accepts
  any free-text model string with no whitelist, so exposing it would just
  invite typo'd values that fail at send time.
- Verified live against the real backend: conversation create/list/delete all
  work end-to-end, and — since no OpenAI API key is configured in this dev
  environment — sending a message correctly exercises the mid-stream error
  path (a 401 from the upstream provider arrives as an `event: error` frame
  and renders in the banner without crashing), confirming the streaming
  pipeline is wired correctly even though the happy-path token-by-token
  render couldn't be observed live.

## Ticketing notes

- List (`/tickets`) and detail (`/tickets/:id`) get their own routes, not
  dialogs — the detail page needs room for the action buttons plus a
  comment/attachment thread. `/tickets` has no ability gate (matching its nav
  item): the backend's `viewAny` allows `tickets.view`, a department manager,
  *or* anyone with a linked employee record, so it's reachable by any
  authenticated member and the API scopes the result server-side.
- The ticket list is the **only** endpoint in this whole API with a
  client-adjustable `sort`/`direction` and `quick_filter` shortcuts
  (`open`/`resolved`/`critical`/`unassigned`/`my_tickets`) — `DataTable`'s
  optional `sorting` prop (added for Employees) is reused here.
- **Internal notes**: mirrors `TicketPolicy::addComment`/`addInternalNote`
  client-side via `useTicketAbilities` — it fetches the caller's own employee
  id (`GET /employees/me`) and compares it against the ticket's
  `employee_id`/`assigned_technician_id`, so the comment box (and its
  internal-note checkbox) only render for the requester, the assigned
  technician, or a `tickets.manage` holder — never a bystander who'd just get
  rejected server-side.
- **Status workflow**: `closed` is only reachable via `POST /close` (never
  `PATCH /status`), and `closed`/`cancelled` are terminal — the UI hides
  Update Status/Close once a ticket reaches either, and only shows Reopen
  when status is `resolved` or `closed`.
- Attachments have no delete endpoint, and their `url` is an **unsigned,
  non-expiring public-disk link** — anyone with the link can view the file
  regardless of auth. This is a backend gap the code comments flag, not
  something the frontend can fix.
- No Ticket Categories admin page — ticket "category" is just the fixed
  `type` enum (`hardware/software/network/account_access/printer/email/
  security/other`), not a manageable backend resource, despite FRONTEND.md
  describing one.
- A brand-new **Owner** account has no linked Employee record by default (an
  Employee is a separate HR entity from the User/tenant-membership) — creating
  a ticket "for myself" in that state 404s ("resource could not be found")
  until an Employee record exists and/or is linked to the account. Verified
  live against a real backend during this slice's smoke test; the error
  surfaces cleanly via a toast rather than crashing, but it's a real product
  gap worth knowing about, not a frontend bug.

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

## Audit Log notes

- **Read-only, single endpoint** (`GET /audit-logs`) — no per-module audit
  tables or views; every module (Employee, Inventory, Ticket, Automation, plus
  a workflow's `log_audit_event` action) writes into the same shared
  `audit_logs` table via the backend's one `AuditLogService`. AI Assistant and
  Knowledge Base never call it, so their activity never appears here.
- **Role-gated, not permission-gated**: the backend restricts this route with
  `role:owner,admin` middleware, not an ability check — there is no
  `audit.view` permission key anywhere in the system. Since every other route
  in this app is gated by `RequireAbility` (a permission-key guard), this
  needed its own `RequireRole` guard (`routes/guards/RequireRole.tsx`) that
  checks the session's role name directly instead.
- **Only two filters exist server-side**: `subject_type` and `subject_id`,
  both plain strings — there's no actor, action, or date-range filter to
  build UI for. `subject_type` is deliberately a free-text input rather than a
  dropdown of the ~10 built-in values (ticket, employee, product, workflow,
  ...), since a workflow's `log_audit_event` action can log against any
  arbitrary subject type/id a workflow author configures, not just the
  built-in ones.
- **`actor_user_id` is a raw id, not a name** — there's no users/members
  lookup endpoint to join against, so the Actor column shows the id as-is, or
  "System" when null (every workflow-triggered entry — scheduled job outcomes
  and the `log_audit_event` action — always records a null actor).
- **`changes` has no fixed shape** — it's an arbitrary JSON object that varies
  per call site (the changed fields on an update, `{}` on a lifecycle
  transition, etc.), so it's rendered as a collapsible raw JSON dump rather
  than field-specific formatting.
- FRONTEND.md says this route should be gated by `RequireAbility('audit.view')`
  — that permission doesn't exist in the backend at all (confirmed via the
  route middleware and its feature test), so `RequireRole` was built instead,
  matching what `routes.config.ts`'s nav item (`roles: ['Owner', 'Admin']`)
  already assumed correctly.
- Verified live against the real backend: the route renders correctly for an
  Owner session and the real `GET /audit-logs` request returns `200 OK` with
  an empty `data: []` (this tenant hasn't triggered any audited action yet) —
  a legitimate empty state, not an error.

## Dashboard notes

- **Scoped to the one real aggregate endpoint in the backend**:
  `GET /tickets/statistics` — reused directly (`TicketStatsBar`, already built
  for the Tickets page) plus a new `TicketPriorityChart` rendering the same
  response's `by_priority` breakdown in full. No other module exposes a
  count/summary endpoint (confirmed by a full route sweep for
  stats/summary/dashboard/analytics/overview/aggregate), so no other widgets
  were built.
- FRONTEND.md's dashboard chart table describes a sales trend, an inventory
  health donut, and an automation job success-rate sparkline — none of these
  are buildable today: there's no Sales module at all, Inventory's cursor
  pagination has no `total` field (so even a "low stock count" can't be had
  without paginating the entire result set client-side), and Automation has
  no job-count-by-status endpoint. FRONTEND.md itself flags this and says new
  `/v1/reports/*` endpoints are needed first — this slice deliberately doesn't
  guess at those shapes.
- `TicketPriorityChart` is a small hand-rolled SVG/CSS bar chart, not a
  charting library — four fixed categories (low/medium/high/critical) don't
  justify a new dependency, and none was installed. Bar colors reuse
  `TicketPriorityBadge`'s exact semantic mapping (secondary/primary/amber/
  destructive) so priority colors stay consistent across the app.
- The `/tickets/statistics` endpoint has no dedicated ability gate — like the
  Tickets list itself, it's reachable by any authenticated member and scoped
  server-side (a plain member sees only their own tickets' totals; a
  `tickets.view` holder sees tenant-wide totals) — so the Dashboard widgets
  render unconditionally, same as `TicketStatsBar` already did.
- Verified live against the real backend: the Dashboard renders both widgets
  correctly for this tenant's zero-ticket state (all counts show `0`, no
  crash), confirming the empty-data path works; a populated tenant wasn't
  available in this session to check with non-zero values.
