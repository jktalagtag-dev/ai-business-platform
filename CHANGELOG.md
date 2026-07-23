# Changelog

All notable changes to this project are documented in this file.
Format loosely follows [Keep a Changelog](https://keepachangelog.com/).

## [Unreleased]

### Added — Frontend redesign to DESIGN_SYSTEM.md, plus a public landing page
- Adopted the root [DESIGN_SYSTEM.md](DESIGN_SYSTEM.md) design language
  across the whole frontend, in nine committed phases: blue-primary
  design tokens (Inter font, a 12/20/24px radius scale, motion tokens),
  restyled controls/surfaces/tables, a 280px sidebar with a profile/
  logout footer, a 64px topbar, and a 12-column dashboard grid. See
  [`frontend/README.md`](frontend/README.md)'s "DESIGN_SYSTEM.md redesign
  notes" section for full detail, including trade-offs.
- **CSV export** on the Employees, Tickets, Products, and Audit Log
  tables — client-side, current-page-only (matches this API's cursor
  pagination), reusing each column's existing cell renderer rather than
  requiring a second "exportable value" definition per column.
- **Docked AI Assistant panel** — a resizable (320–560px), collapsible
  panel next to the main content on any authenticated page (`lg`+ only),
  toggled from the Topbar. Reuses the exact same chat hooks/components as
  the full-page `/ai/conversations/:id` view; only creates a new
  conversation on its first message, never just from being opened.
- **Public marketing landing page** at `/` for signed-out visitors (Hero,
  a module showcase, Automation/AI Assistant/Analytics highlights,
  pricing over the existing `tenants.plan` values, an FAQ, and a closing
  CTA) — authenticated users still see the dashboard at the same path.
  Built entirely from this app's own UI components over illustrative
  example data; no fabricated integrations, case studies, or
  testimonials were added.
- New dependencies: `@fontsource/inter` (self-hosted font) and
  `@radix-ui/react-accordion` (the landing page's FAQ).

### Changed — Default AI Assistant example switched to Gemini
- No code changes: confirmed [Gemini's OpenAI-compatible endpoint](https://ai.google.dev/gemini-api/docs/openai)
  (`https://generativelanguage.googleapis.com/v1beta/openai`) supports
  Chat Completions (streaming + tool-calling) and an Embeddings API, so
  it's a drop-in for the same generic provider used for
  OpenRouter/Ollama/OpenAI below — this project's default example provider
  has moved between all of these at various points without ever touching
  `OpenAiCompatibleProvider` itself. `backend/.env.example`'s `AI_*` block
  now defaults to Gemini (`AI_MODEL=gemini-2.5-flash`,
  `AI_EMBEDDING_MODEL=gemini-embedding-001`, replacing the deprecated
  `text-embedding-004`), with a comment on swapping to OpenRouter or a
  local Ollama instead.
- **Known risk, not yet verified live:** a Google AI Developer Forum report
  describes issues combining `tool_call` deltas with streaming specifically
  on Gemini's compat layer. `ChatService`'s tool-call loop relies on that
  exact combination — this needs a live functional check (ask a question
  that triggers `GetTicketStatisticsTool`/`SearchKnowledgeBaseTool` mid-turn)
  before relying on it in practice.
- Note: switching embedding models changes vector dimensionality (OpenAI's
  `text-embedding-3-small` is 1536-dim; Gemini's `gemini-embedding-001` is
  configurable, commonly used at 768 or 1536-dim; Ollama's
  `nomic-embed-text` is 768-dim). `kb_document_chunks.embedding` is stored
  as `jsonb` rather than a fixed-width column, so no migration is needed,
  but any documents already indexed under a different embedding model won't
  cosine-compare meaningfully against new ones — re-upload existing
  Knowledge Base documents after switching embedding providers.

### Added — OpenRouter (or any OpenAI-compatible endpoint) support for the AI Assistant
- The AI chat provider (`App\Infrastructure\AI\OpenAiCompatibleProvider`) was
  already wire-format compatible with OpenRouter's Chat Completions API, so
  this is a configuration-level change plus two small additions rather than
  a new integration: `config/ai.php` now separates the **chat** provider
  (`AI_BASE_URL`/`AI_API_KEY`/`AI_MODEL`, e.g. pointed at
  `https://openrouter.ai/api/v1`) from the **embeddings** provider
  (`AI_EMBEDDING_BASE_URL`/`AI_EMBEDDING_API_KEY`/`AI_EMBEDDING_MODEL`),
  since OpenRouter's embedding-model coverage is far narrower than its chat
  coverage — Knowledge Base indexing can keep using a real OpenAI key for
  `embed()` while chat traffic goes through OpenRouter. Both new pairs
  default to the existing single-provider values, so an unconfigured
  install behaves exactly as before.
- Added optional `AI_SITE_URL`/`AI_SITE_NAME` config, sent as OpenRouter's
  `HTTP-Referer`/`X-Title` attribution headers on chat requests only when
  set (no effect on plain OpenAI or other providers that ignore them).
- Documented all `AI_*` env vars in `backend/.env.example` (previously
  undocumented there despite being read by `config/ai.php`).
- New tests: `tests/Feature/Ai/OpenAiCompatibleProviderTest.php` asserts
  attribution headers are only sent when configured, and that `embed()`
  hits the embedding-specific base URL/key independently of the chat one.

### Added — Ticket assignment restricted to the ticket's department
- `TicketService::assign()` previously accepted any employee id in the
  tenant as a technician, with zero eligibility check beyond the actor
  holding `tickets.manage`. Now rejects assigning a technician whose
  `department_id` doesn't match the ticket's own `department_id`, via a new
  `App\Domain\Shared\Exceptions\InvalidTechnicianAssignmentException`
  (mirrors `InvalidManagerAssignmentException`'s shape) mapped to a 422 with
  a `technician_employee_id` field-level error in `bootstrap/app.php`,
  matching `InsufficientStockException`'s existing pattern so the frontend's
  `applyApiErrorsToForm` can surface it inline. A ticket with no department
  (an unlinked requester) isn't restricted — nothing to match against. Paired
  with a frontend fix scoping the technician picker (`EmployeeSelect`'s new
  optional `departmentId` prop) to the same rule, so the UI and the API now
  agree instead of the UI silently allowing choices the API was never
  actually validating.

### Added — Demo data seeder
- `database/seeders/DemoDataSeeder.php`, called from `DatabaseSeeder` after
  `RolePermissionSeeder`: creates a dedicated **Demo Company** tenant (login
  `demo@example.com` / `password123`) and populates 15+ realistic rows in
  every module — departments, positions, employees, product categories,
  products, suppliers, stock, workflows, tickets, AI conversations, and
  Knowledge Base documents — so a fresh `php artisan migrate --seed` gives a
  fully browsable demo, not an empty shell.
- Records are created through the real Application Services (not raw
  Eloquent inserts) wherever that matters — `EmployeeService`,
  `TicketService`, `WorkflowService`, etc. — so employee/ticket numbering,
  the auto-provisioned stock record on product creation, and Audit Log
  entries are all generated exactly like real HTTP-driven usage would
  produce. Workflows are seeded and activated *before* tickets, so creating
  the demo tickets fires real `ticket.created`/`ticket.assigned`/
  `ticket.status_changed` events against them; the seeder then drains the
  `automation`/`notifications`/`knowledge_base` queues itself
  (`Artisan::call('queue:work', ['--stop-when-empty' => true])`) so the
  resulting Automation Jobs and notification jobs already show settled
  `succeeded`/`failed` statuses rather than sitting `queued` forever.
- AI conversations/messages and Knowledge Base documents/chunks are the one
  exception, inserted directly via Eloquent — seeding them "for real" would
  require a working LLM API key and real PDF files respectively, neither of
  which a seeder should depend on.
- Idempotent: a second `db:seed` run is a no-op once the Demo Company tenant
  exists (checked by name) — delete the tenant (cascading through its rows)
  to reseed from scratch.
- Uncovered along the way: Policies in this codebase authorize via the
  *current Sanctum token's* abilities (`AuthorizesViaTokenAbilities`), not
  the user/role directly — there's no HTTP request in a console seeder to
  populate that via middleware, so the seeder explicitly attaches the real
  token `AuthService::register()` issues to the actor
  (`$owner->withAccessToken(...)`) before calling any other service.

### Added — Automation Engine module
- Event-driven workflow triggers: a single `AutomationEventSubscriber`
  (registered via `Event::subscribe()` from the module's own service
  provider) matches active workflows against the six domain events this
  codebase already fires (`TicketCreated`, `TicketAssigned`,
  `TicketStatusChanged`, `EmployeeCreated`, `EmployeeUpdated`,
  `EmployeeArchived`) — zero edits to the Ticketing/Employee modules'
  service providers were needed.
- Workflow execution: `Workflow` has ordered `WorkflowStep`s (one
  `trigger`, zero-or-more `condition`s, one-or-more `action`s). A pure
  `Domain\Automation\ConditionEvaluator` evaluates conditions against the
  trigger's flat context array; a non-matching condition short-circuits
  the remaining steps as `skipped` (a successful "did nothing" outcome,
  not a failure).
- Actions: an extensible `ActionRegistry` (same pattern as the AI
  Assistant's tool registry) with two built-in actions —
  `send_notification` (generic email via Laravel's on-demand
  notifications) and `log_audit_event` (writes to the existing, shared
  `audit_logs` table). Both support `{{field.path}}` placeholder
  substitution from the trigger context (`Domain\Automation\
  PlaceholderResolver`).
- Queue workers / background jobs: `ExecuteWorkflowJob` on a new
  `automation` queue, following the Ticketing module's established
  tenant-context-carrying job pattern.
- Retry handling: self-managed rather than Laravel's `$tries`/`backoff()`
  — `automation_jobs.attempts`/`max_attempts` is the single source of
  truth (visible via the API). On failure, the job re-dispatches itself
  with an increasing delay until `max_attempts`, then marks the job
  permanently `failed`. `POST /v1/automation/jobs/{id}/retry` additionally
  lets an Owner/Admin manually reset and re-run a failed job.
- Scheduled jobs: a `kind: "schedule"` trigger takes a cron expression
  (parsed via `dragonmantank/cron-expression` — already a transitive
  dependency of Laravel's own scheduler, so no new package was installed).
  `RunScheduledWorkflowsJob` checks every active scheduled workflow each
  minute; a new `workflows.last_triggered_at` column de-dupes a workflow
  firing twice for the same due minute.
- Audit logs: every workflow/job lifecycle transition (workflow created/
  activated/paused/deleted, job started/succeeded/failed, manual retry)
  is recorded via the existing `AuditLogService`/`audit_logs` table,
  reused as-is — no new audit infrastructure.
- `automation.view`/`automation.manage` permissions added to the RBAC
  seed, granted **Owner/Admin only** — unlike Ticketing/Knowledge Base,
  workflow authoring can trigger emails and write audit entries, so it's
  treated as an administrative capability, not broad internal-user access.
- Scoped deliberately against the full aspirational design in
  ARCHITECTURE.md §10/BACKEND.md: no Redis/Horizon (this project's plain
  database/sync queue driver is used as-is), no inbound webhooks or
  OAuth-based `integration_connections` this round — event triggers are
  limited to events this codebase already fires, not third-party sources.
- Feature and unit test coverage: workflow CRUD/validation/authorization,
  full event-triggered execution end-to-end (via the real Ticket-creation
  flow), condition short-circuiting, the failure → retry → permanent-
  failure path (via a test-only always-failing action), manual retry, the
  `log_audit_event` action's audit trail, and Domain unit tests
  (`ConditionEvaluator`, `PlaceholderResolver`, `ActionRegistry`).

### Added — Knowledge Base module
- PDF upload (`POST /v1/knowledge-base/documents`, multipart), self-contained
  storage (not a generic `files` table, same precedent as Ticket attachments
  and Employee avatars). Processing (extraction, chunking, embedding) runs
  asynchronously via a queued `ProcessKnowledgeBaseDocumentJob`.
- Text extraction via the new `smalot/pdfparser` dependency (pure PHP, no
  system binary), abstracted behind `PdfTextExtractorInterface` so it's
  swappable and fully fake-able in tests.
- Chunking: a simple fixed-size sliding-window chunker with overlap
  (`Domain\KnowledgeBase\TextChunker`, `config('knowledge_base.chunk_size')`/
  `chunk_overlap`), one page's text at a time — chunks never cross a page
  boundary, so every citation can name a page number.
- Embeddings: `AiProviderInterface` (from the AI Assistant module) gained
  an `embed()` method hitting the same OpenAI-compatible `/embeddings`
  endpoint, configured via `config('ai.embedding_model')` (default
  `text-embedding-3-small`) — no new provider abstraction needed.
- Vector storage: `kb_document_chunks.embedding` is a jsonb array of floats,
  **not** a native pgvector column — this project's test suite runs on
  SQLite (no pgvector support), and every other module has taken the same
  SQL-portability trade-off. Retrieval does brute-force cosine similarity
  in PHP (`Domain\KnowledgeBase\VectorMath`); fine at typical internal-KB
  volumes. A real vector column + ANN index is a natural (separately
  schema-approved) follow-up if a tenant's corpus outgrows it.
- Retrieval + answer generation: `POST /v1/knowledge-base/ask` embeds the
  query, ranks chunks by cosine similarity, and generates an answer from a
  numbered-context, cite-your-sources prompt — returning `answer`,
  `citations` (document/title/page/snippet/score per source), and token
  usage. An empty knowledge base short-circuits to a fixed fallback answer
  without calling the provider.
- Citation support integrated into the **existing** AI Assistant chat too:
  a new `search_knowledge_base` function-calling tool
  (`Application/Services/AI/Tools/SearchKnowledgeBaseTool`) lets chat
  conversations cite the knowledge base directly, sharing the same
  retrieval service and citation formatting (`CitationBuilder`) as the
  standalone `/ask` endpoint.
- `knowledge_base.view`/`knowledge_base.manage` permissions added to the
  RBAC seed. `knowledge_base.view` (search/ask, list/view documents) is
  granted to Owner, Admin, **and Member** by default — a company knowledge
  base is usually meant for broad internal use; `knowledge_base.manage`
  (upload/delete) stays Owner/Admin only.
- Feature and unit test coverage: the full upload → process → ready/failed
  pipeline (via `FakePdfTextExtractor` and `FakeAiProvider` test doubles —
  no real network calls or PDF fixtures needed), authorization, ask/citation
  behavior including ranking, the chat tool integration, and Domain unit
  tests (`TextChunker`, `VectorMath`).

### Added — AI Assistant module
- OpenAI-compatible chat: `Infrastructure/AI/OpenAiCompatibleProvider` speaks
  the standard Chat Completions streaming wire format against
  `config('ai.base_url')` (real OpenAI by default) using Laravel's built-in
  HTTP client — no new Composer package.
- Conversations (`/v1/ai/conversations`) with per-conversation `title`,
  `system_prompt` (falls back to a config default), and `model`; private to
  the user who created them (no Owner/Admin "view all" — unlike every other
  domain, an AI chat is treated as personal, not tenant business data).
- Conversation history (`ai_messages`, paginated via
  `GET /v1/ai/conversations/{id}/messages`) and context memory: each turn
  sends the system prompt plus the most recent `context_window_messages`
  (default 30) as a sliding window — a documented v1 simplification, not
  true token-budget truncation.
- Streaming replies: `POST /v1/ai/conversations/{id}/messages` responds
  `text/event-stream` (`user_message`, `delta`, `tool_call`, `tool_result`,
  `message`, `error` frames). Ownership is authorized *before* streaming
  begins, so an invalid/foreign conversation id still gets a normal JSON
  403/404 — only mid-completion failures surface as an `error` frame.
  A new `AiProviderException` → 502 mapping was added to
  `bootstrap/app.php` for upstream provider failures.
- Function calling: `AiToolInterface` + `AiToolRegistry`, with two built-in
  tools — `get_current_datetime` (no auth) and `get_ticket_statistics`
  (delegates to the existing, already-authorized `TicketStatisticsService`,
  so RBAC scoping is inherited for free with no Ticketing code touched). An
  unknown or failing tool call is fed back to the model as an error result
  rather than aborting the request; a runaway tool-calling loop is capped
  by `max_tool_iterations` (default 5, throwing
  `AiToolIterationLimitExceededException`).
- Token tracking: every message records `prompt_tokens`/`completion_tokens`
  when the provider reports them (via `stream_options.include_usage`);
  `ai_conversations` keeps a running `total_prompt_tokens`/
  `total_completion_tokens`.
- No RAG: `documents`, `document_chunks`, `knowledge_base_*`, and
  `embeddings` remain unimplemented, per scope. The Ticketing module's
  existing AI-prep interfaces (categorization, priority prediction, etc.)
  were left untouched and unbound — this module is a separate, general-
  purpose assistant, not ticket-specific AI.
- Feature and unit test coverage: conversation CRUD/ownership, streaming
  (via a `FakeAiProvider` test double — no real network calls), the
  tool-calling loop (success, unknown tool, iteration limit), context
  window construction, token tracking, and Domain/registry unit tests.

### Added — IT Ticketing module
- Ticket CRUD with system-generated `ticket_number` (`TCK-000123`,
  atomically issued per tenant via `ticket_id_sequences`, mirroring the
  Employee module's `employee_id_sequences` pattern).
- Full lifecycle: create → assign/reassign a technician → work (status
  transitions) → close (with required resolution notes) → reopen.
  `PATCH /v1/tickets/{id}/status` deliberately excludes `closed` as a
  target — `POST /v1/tickets/{id}/close` is the only path there, since it
  requires `resolution_notes`.
- Comments (`/v1/tickets/{id}/comments`) with an `is_internal` flag —
  internal notes are hidden from the requester and can only be added by
  staff with `tickets.manage` or the assigned technician.
- Attachments (`/v1/tickets/{id}/attachments`, multipart, 10MB max),
  self-contained (not backed by a generic `files` table), consistent with
  the Employee module's avatar-upload precedent.
- Dashboard statistics (`/v1/tickets/statistics`): open/closed counts,
  average resolution time, and breakdowns by department, priority, and
  technician.
- Security model: any employee may create tickets for themselves and view
  their own; the assigned technician may update/close/reopen their
  assigned tickets; a department's manager sees that department's tickets;
  `tickets.manage`/`tickets.view` grant full cross-tenant access (Owner
  and Admin by default). "Technician" and "department manager" are
  structural relationships, not distinct roles — the same pattern the
  Employee module established for department managers.
- Queue-backed notifications: technician notified on assignment, requester
  notified on status change, department manager (and assigned technician)
  notified for Critical-priority tickets and for SLA breaches. A scheduled
  `SlaMonitoringJob` (every 15 minutes) compares each open ticket's age
  against its priority's resolution-time target (`SlaPolicy`) and escalates
  once per breach (`tickets.sla_breached_at`, cleared on reopen).
- AI-ready, AI-free architecture: `Application/Contracts/Services/Ticket/
  Ai/` defines interfaces for categorization, priority prediction, solution
  suggestion, knowledge-base search, and summarization — none are bound to
  an implementation; no AI behavior ships with this module.
- `tickets.view`/`tickets.manage` permissions added to the RBAC seed
  (Owner and Admin granted both; Members granted neither, since ordinary
  employee/technician/manager access is resolved structurally, not via a
  permission grant).
- Feature and unit test coverage: CRUD, full workflow (assign/reassign,
  status transitions, invalid-transition rejection, close/reopen),
  authorization (requester/technician/manager/admin scoping), comment
  visibility, attachment upload/validation, dashboard statistics, queued
  job dispatch, and pure Domain/SlaPolicy unit tests.

### Added — Employee Management module
- Employee CRUD with system-generated `employee_number` (`EMP-000123`,
  atomically issued per tenant — see `employee_id_sequences` in DATABASE.md).
- Departments (optionally hierarchical, with a designated manager) and
  Positions as first-class, tenant-scoped entities.
- Employee profile fields: emergency contact, mailing address, bio, and
  profile picture upload (`POST /v1/employees/{id}/avatar`).
- Employee Notes (`/v1/employees/{id}/notes`) — internal notes visible to
  Owner/Admin/HR and the employee's department manager.
- Self-service profile updates: an employee may update their own contact
  fields; changing department, position, manager, status, or dates
  requires the `employees.manage` permission (Owner/Admin/HR).
- Department-manager-scoped visibility: a department's designated manager
  can list and view employees within that department without holding the
  broader `employees.view` permission.
- New `HR` system role, and `employees.*`/`departments.*`/`positions.*`
  permissions added to the RBAC seed (Owner and Admin also granted manage
  access; Members are not granted directory-wide view by default).
- Granular audit trail (reusing the existing `audit_logs` table): create,
  update, archive, department/position/manager/status changes, profile
  updates, note additions, and avatar uploads are each recorded with a
  distinct action.
- `EmployeeCreated`, `EmployeeUpdated`, `EmployeeArchived` domain events,
  fired from `EmployeeService` for future listeners (e.g. notifications)
  to subscribe to.
- Feature and unit test coverage: CRUD, validation, cross-tenant isolation,
  self-vs-manager-vs-HR authorization, stock-style audit assertions, and
  pure Domain-entity unit tests.

### Fixed
- Cursor pagination (`ApiResponse::paginated()`) threw a 500 error on any
  list endpoint once a tenant had more rows than one page, because
  Laravel's cursor computation reads the ORDER BY column directly off each
  paginated item — which broke once repositories mapped Eloquent models
  into plain Domain entities via `->through()` before the cursor was read.
  Introduced `CachedCursorPaginator` (captures the cursor before the
  Eloquent→Domain mapping runs) and applied it across every paginated
  repository in the Auth, Inventory, and Employee modules. This was a
  latent bug in shared infrastructure, not something introduced by the
  Employee module — it had simply never been exercised by existing tests,
  none of which had produced more than one page of results.

## Inventory Management module
- Products, Categories, Suppliers, and Stock (inventory ledger) with full
  CRUD, tenant-scoped validation, RBAC-gated Policies, and an auto-
  provisioned single warehouse per tenant.
- Stock is modeled as an append-only movement ledger (`inventory_movements`)
  with a cached `quantity_on_hand` on `inventory_items`, adjusted only
  through `POST /v1/stock/{product}/adjust` (never edited directly).
- Shared `TenantContext` / `ResolveTenant` middleware introduced here,
  since Inventory was the first module needing tenant-scoped data access
  beyond the Auth module's own tables.
- Generic `audit_logs` table and `AuditLogService`, reused by every module
  built since.

## Authentication & RBAC module
- Laravel Sanctum token-based authentication: register, login, logout,
  forgot/reset password, profile view/update.
- Custom database-driven RBAC (`roles`, `permissions`, `role_permissions`,
  `tenant_users`) with system roles Owner/Admin/Member, seeded via
  `RolePermissionSeeder`.
- Role-based route middleware (`role:owner,admin`) reading abilities
  encoded on the Sanctum token at issuance.
- Swagger/OpenAPI documentation generated from PHP attributes on
  Controllers, served at `/api/documentation`.
- Full Pest feature test coverage for every auth flow.

## Architecture & documentation
- Enterprise architecture, backend, frontend, database, and API design
  documents (`ARCHITECTURE.md`, `BACKEND.md`, `FRONTEND.md`, `DATABASE.md`,
  `API.md`) written before any implementation, establishing Clean
  Architecture (Domain → Application → Infrastructure → Http), the
  Repository Pattern, Service Layer, and REST/response-envelope
  conventions every module since has followed.
