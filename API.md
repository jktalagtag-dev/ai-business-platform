# API Structure

Status: Draft v1.0 (design phase, no code written yet)
Related: [ARCHITECTURE.md](ARCHITECTURE.md) · [DATABASE.md](DATABASE.md)

Defines the REST conventions every endpoint in the platform must follow, so
responses are predictable regardless of which Controller/Service produced
them.

## 1. Base URL & Versioning

```
https://api.<domain>/api/v1/...
```

- URI versioning (`/v1`, `/v2`) — never breaking changes within a version.
- A version is only bumped for breaking changes; additive fields/endpoints
  ship within the current version.
- Each tenant's requests are scoped either by subdomain
  (`{tenant}.app.<domain>`) or an `X-Tenant-Id` header for
  server-to-server integrations — resolved once in middleware into the
  `TenantContext` used throughout the backend (see ARCHITECTURE.md §8).

## 2. Resource Naming & Routing

- Nouns, plural, kebab-case: `/workflows`, `/ai-conversations`,
  `/document-uploads`.
- Standard REST verbs map to standard actions:

| Method | Path | Action |
|---|---|---|
| GET | `/v1/workflows` | list (paginated) |
| POST | `/v1/workflows` | create |
| GET | `/v1/workflows/{id}` | show |
| PATCH | `/v1/workflows/{id}` | partial update |
| DELETE | `/v1/workflows/{id}` | delete (soft delete by default) |
| POST | `/v1/workflows/{id}/activate` | non-CRUD action on a resource |

- Non-CRUD actions (`activate`, `duplicate`, `send`) are modeled as `POST`
  sub-resources/verbs, not overloaded query params.
- Nested resources only one level deep:
  `/v1/workflows/{id}/runs`, not `/v1/workflows/{id}/runs/{runId}/steps/...`
  beyond what's genuinely a child collection — deeper access goes through
  its own top-level, filterable resource (`/v1/workflow-step-runs?run_id=`).

## 3. Response Envelope

Every response — success or error — uses the same top-level shape so
frontend clients never special-case a response format.

### 3.1 Single resource

```json
{
  "data": {
    "id": "wf_01HXYZ...",
    "type": "workflow",
    "attributes": {
      "name": "New lead follow-up",
      "status": "active"
    }
  },
  "meta": {
    "request_id": "req_9f8c..."
  }
}
```

### 3.2 Collection (cursor-paginated)

```json
{
  "data": [
    { "id": "wf_01H...", "type": "workflow", "attributes": { "...": "..." } }
  ],
  "meta": {
    "request_id": "req_9f8c...",
    "pagination": {
      "next_cursor": "eyJpZCI6...",
      "prev_cursor": null,
      "per_page": 25
    }
  }
}
```

- **Cursor pagination**, not page numbers — stable under concurrent
  writes, and required for any endpoint whose collection can grow large
  (workflow runs, AI usage logs, conversation messages).
- `id` fields are opaque, prefixed, sortable strings (ULID), never raw
  auto-increment integers — avoids leaking row counts and keeps IDs safe
  to expose across tenants.

### 3.3 Error envelope

```json
{
  "error": {
    "code": "validation_failed",
    "message": "The given data was invalid.",
    "details": [
      { "field": "name", "message": "The name field is required." }
    ]
  },
  "meta": {
    "request_id": "req_9f8c..."
  }
}
```

| HTTP status | `error.code` | When |
|---|---|---|
| 400 | `bad_request` | Malformed request |
| 401 | `unauthenticated` | Missing/invalid credentials |
| 403 | `forbidden` | Authenticated but not authorized (Policy denial) |
| 404 | `not_found` | Resource doesn't exist or isn't in the caller's tenant |
| 409 | `conflict` | State conflict (e.g. duplicate activation) |
| 422 | `validation_failed` | Form Request validation failure |
| 429 | `rate_limited` | Rate limit exceeded (includes `Retry-After` header) |
| 5xx | `internal_error` | Unhandled server error (never leaks internals in `message`) |

Every error response is produced by the same central exception handler
(ARCHITECTURE.md §5), so Controllers never hand-build error JSON.

## 4. Cross-Cutting Conventions

- **Idempotency**: mutating requests that trigger external side effects
  (AI calls, webhook dispatch, billing) accept an `Idempotency-Key` header;
  replays with the same key return the original response instead of
  re-executing.
- **Filtering/sorting**: `?filter[status]=active&sort=-created_at` —
  consistent query param shape across all list endpoints.
- **Partial responses**: `?fields=id,name` supported on read-heavy
  endpoints to reduce payload size for dashboard widgets.
- **Rate limiting headers**: `X-RateLimit-Limit`, `X-RateLimit-Remaining`,
  `Retry-After` on every response, tightest on AI-backed endpoints (real
  provider cost per call — see ARCHITECTURE.md §9).
- **Correlation**: every response includes `meta.request_id`, propagated
  into logs/traces for support and debugging.
- **Streaming**: AI completion endpoints (`POST /v1/ai/conversations/{id}/messages`)
  respond with `text/event-stream` when `Accept: text/event-stream` is
  sent, emitting incremental token events; otherwise they block and
  return the full envelope above.
- **System-generated identifiers**: any field the platform derives rather
  than accepts from the client (e.g. `employees.employee_number`, formatted
  `EMP-000123`) is silently ignored if present in the request body — the
  server-computed value always wins. These are returned in the response but
  are never accepted as writable input, and are documented as read-only in
  the OpenAPI schema.

## 5. Contract-First Development

- The OpenAPI 3.1 spec (`docs/api/openapi.yaml`) is the source of truth for
  every endpoint — written/updated before or alongside implementation, not
  reverse-engineered after.
- Frontend TypeScript request/response types are generated from that spec,
  eliminating hand-maintained duplicate type definitions.
- CI validates actual Controller/Resource output against the spec
  (contract tests — ARCHITECTURE.md §17) so drift fails the build instead
  of surfacing as a runtime bug.

## 6. Endpoint Domains

### 6.1 Implemented

| Domain | Base path | Notes |
|---|---|---|
| Auth | `/v1/auth/register`, `/v1/auth/login`, `/v1/auth/logout`, `/v1/auth/forgot-password`, `/v1/auth/reset-password` | Sanctum token issuance; register also provisions the tenant + Owner membership |
| Profile & RBAC | `/v1/profile`, `/v1/roles` | Self profile view/update; role catalogue is read-only (Owner/Admin only) |
| Inventory | `/v1/categories`, `/v1/products`, `/v1/suppliers`, `/v1/stock`, `/v1/stock/{product}/adjust`, `/v1/stock/{product}/movements` | Full CRUD + stock ledger; see BACKEND.md's Inventory module |
| Employee Management | `/v1/departments`, `/v1/positions`, `/v1/employees`, `/v1/employees/me`, `/v1/employees/{id}/avatar`, `/v1/employees/{id}/notes` | Full CRUD, self-service profile updates, department-manager-scoped visibility — see below |
| IT Ticketing | `/v1/tickets`, `/v1/tickets/statistics`, `/v1/tickets/{id}/assign`, `/v1/tickets/{id}/status`, `/v1/tickets/{id}/close`, `/v1/tickets/{id}/reopen`, `/v1/tickets/{id}/comments`, `/v1/tickets/{id}/attachments` | Full lifecycle (create → assign → work → close/reopen), comments with internal notes, attachments, dashboard statistics — see below |
| AI Assistant | `/v1/ai/conversations`, `/v1/ai/conversations/{id}/messages` | OpenAI-compatible chat with streaming (SSE), function calling, and per-conversation token tracking — see below |
| Knowledge Base | `/v1/knowledge-base/documents`, `/v1/knowledge-base/ask` | PDF upload → chunking → embeddings → retrieval → cited answer generation — see below |
| Automation Engine | `/v1/automation/workflows`, `/v1/automation/jobs` | Event-driven + scheduled workflow execution, retry handling, run history — see below |
| Audit Logs | `/v1/audit-logs` | Read-only, Owner/Admin/HR only; `subject_type`/`subject_id` filter across every domain's audit trail |

### 6.2 Planned (not yet built)

| Domain | Base path | Notes |
|---|---|---|
| Webhooks (inbound) | `/v1/webhooks/{tenant}/{integration}` | HMAC-signed, unauthenticated by session — would extend the Automation Engine with a third trigger kind alongside event/schedule |
| Billing | `/v1/billing/subscription`, `/v1/billing/invoices` | read-mostly, mutations via billing provider webhook |

Detailed request/response schemas per implemented endpoint are generated
from PHP attributes on the Controllers (see BACKEND.md) and served live at
`/api/documentation` (Swagger UI) rather than hand-maintained here.

### 6.3 Employee Management — endpoint detail

| Method | Path | Action | Auth |
|---|---|---|---|
| GET | `/v1/departments` | List departments | `departments.view` |
| POST | `/v1/departments` | Create department | `departments.manage` |
| GET/PATCH/DELETE | `/v1/departments/{id}` | Show/update/delete | `departments.view` / `departments.manage` |
| GET/POST | `/v1/positions` | List/create positions | `positions.view` / `positions.manage` |
| GET/PATCH/DELETE | `/v1/positions/{id}` | Show/update/delete | `positions.view` / `positions.manage` |
| GET | `/v1/employees` | List employees, filter/search/sort/paginate | `employees.view`, or department-manager (auto-scoped) |
| POST | `/v1/employees` | Create employee (server generates `employee_number`) | `employees.manage` (Owner/Admin/HR) |
| GET | `/v1/employees/me` | The caller's own linked employee record | any authenticated member with a linked record |
| GET | `/v1/employees/{id}` | Show one employee | `employees.view`, self, or manager of that employee's department |
| PATCH | `/v1/employees/{id}` | Update — self may only change contact/profile fields; department/position/manager/status/dates require `employees.manage` | see above |
| DELETE | `/v1/employees/{id}` | Archive (soft-delete) | `employees.manage` |
| POST | `/v1/employees/{id}/avatar` | Upload profile picture (multipart) | self or `employees.manage` |
| GET/POST | `/v1/employees/{id}/notes` | List/add internal notes | `employees.view`/`employees.manage`, or department manager (add only) |

A `PATCH /v1/employees/{id}` that includes a changed employment-only field
(`department_id`, `position_id`, `manager_employee_id`, `employment_status`,
`hire_date`, `termination_date`) from a caller who only has self-access
returns `403 forbidden` — the endpoint is shared, but field-level
authorization is enforced in the Service, not split across two endpoints.

### 6.4 IT Ticketing — endpoint detail

| Method | Path | Action | Auth |
|---|---|---|---|
| GET | `/v1/tickets` | List tickets, filter/search/paginate; `quick_filter=open\|resolved\|critical\|my_tickets\|unassigned` | `tickets.view`, department manager (dept-scoped), or any employee with a linked record (self/assigned-scoped) |
| POST | `/v1/tickets` | Create a ticket (server generates `ticket_number`) | any employee, for themselves; `employee_id` override requires `tickets.manage` |
| GET | `/v1/tickets/statistics` | Dashboard: open/closed counts, average resolution time, breakdowns by department/priority/technician | same visibility rule as list |
| GET | `/v1/tickets/{id}` | Show one ticket | `tickets.view`, requester, assigned technician, or manager of the requester's department |
| PATCH | `/v1/tickets/{id}` | Update ticket content (type/priority/subject/description/resolution notes) | `tickets.manage` or the assigned technician — **not** the requester |
| POST | `/v1/tickets/{id}/assign` | Assign or reassign a technician | `tickets.manage` |
| PATCH | `/v1/tickets/{id}/status` | Change status (excludes `closed` — use `/close`) | `tickets.manage` or the assigned technician |
| POST | `/v1/tickets/{id}/close` | Close with required `resolution_notes` | `tickets.manage` or the assigned technician |
| POST | `/v1/tickets/{id}/reopen` | Reopen a resolved/closed ticket | `tickets.manage`, the assigned technician, or the requester |
| GET/POST | `/v1/tickets/{id}/comments` | List/add comments; `is_internal: true` for a staff-only note | list: same as show; internal notes visible only to `tickets.manage`/technician; adding an internal note requires `tickets.manage` or the assigned technician (not the requester) |
| GET/POST | `/v1/tickets/{id}/attachments` | List/upload attachments (multipart, 10MB max) | same as adding a comment |

Ticket type is one of `hardware`, `software`, `network`, `account_access`,
`printer`, `email`, `security`, `other`; priority is one of `low`,
`medium`, `high`, `critical`; status is one of `open`, `assigned`,
`in_progress`, `waiting_for_user`, `resolved`, `closed`, `cancelled`.
"Technician" and "department manager" are not distinct system roles —
they are structural relationships (`tickets.assigned_technician_id` and
`departments.manager_employee_id` respectively), checked per-ticket by
`TicketPolicy` rather than granted as a permission, the same pattern the
Employee module established for department managers.

The module is deliberately AI-ready but AI-free: `Application/Contracts/
Services/Ticket/Ai/` defines interfaces for categorization, priority
prediction, solution suggestion, knowledge-base search, and
summarization, but none are bound to an implementation — no AI behavior
ships with this module.

### 6.5 AI Assistant — endpoint detail

| Method | Path | Action | Auth |
|---|---|---|---|
| GET | `/v1/ai/conversations` | List the caller's own conversations | any authenticated tenant member |
| POST | `/v1/ai/conversations` | Start a conversation (`title`, `system_prompt`, `model` all optional, falling back to config defaults) | any authenticated tenant member |
| GET | `/v1/ai/conversations/{id}` | Show one conversation | owner only |
| DELETE | `/v1/ai/conversations/{id}` | Delete a conversation and its message history | owner only |
| GET | `/v1/ai/conversations/{id}/messages` | Paginated message history (conversation history) | owner only |
| POST | `/v1/ai/conversations/{id}/messages` | Send a message; streams the assistant's reply as Server-Sent Events | owner only |

A conversation is private to the user who created it — there is
deliberately no "view all conversations" ability for Owner/Admin, unlike
every other domain in this API. An AI assistant chat is treated as
personal to the user, not tenant business data.

**Streaming.** `POST .../messages` responds `Content-Type:
text/event-stream` rather than the usual JSON envelope. Frames are `event:
<name>\ndata: <json>\n\n`:

| Event | Payload | When |
|---|---|---|
| `user_message` | `{id, content}` | immediately, once the user's message is persisted |
| `delta` | `{content}` | for each incremental fragment of the assistant's reply as it streams in |
| `tool_call` | `{id, name, arguments}` | when the model requests a function call |
| `tool_result` | `{id, name, result}` | once that function has executed |
| `message` | `{id, content, usage: {prompt_tokens, completion_tokens}}` | the final assistant message, once no further tool calls are requested |
| `error` | `{message}` | a failure occurring *after* streaming has already started (e.g. the upstream provider erroring, or the tool-call iteration limit being exceeded) — anything that would otherwise be a clean 4xx/5xx is returned as a normal JSON error *before* streaming begins instead |

**System prompts.** Each conversation carries its own optional
`system_prompt`; when null, `config('ai.default_system_prompt')` is used.

**Context memory.** Each turn sends the system prompt plus the most recent
`config('ai.context_window_messages')` messages (default 30) as context —
a simple sliding window by message count, not true token-budget-based
truncation. This is a documented v1 simplification.

**Function calling.** The assistant can call three built-in functions:
`get_current_datetime` (no authorization needed), `get_ticket_statistics`
(delegates to the existing, already-authorized `TicketStatisticsService` —
so the same visibility scoping a human caller of `GET /v1/tickets/statistics`
gets applies automatically), and `search_knowledge_base` (delegates to the
Knowledge Base module's retrieval service — see §6.6 — so the chat can
cite uploaded documents directly). An unknown or failing tool call is fed
back to the model as `{"error": "..."}` rather than aborting the request,
and a runaway tool-calling loop is capped by `config('ai.max_tool_iterations')`
(default 5).

**Token tracking.** Every message records its own `prompt_tokens`/
`completion_tokens` when the provider reports them; `ai_conversations`
keeps a running `total_prompt_tokens`/`total_completion_tokens`.

**OpenAI-compatible.** The provider (`config('ai.base_url')`, default the
real OpenAI API) speaks the standard Chat Completions streaming wire
format, so any compatible endpoint works by changing config alone. The
project's current default example is **[Gemini](https://ai.google.dev/gemini-api/docs/openai)**
(`AI_BASE_URL=https://generativelanguage.googleapis.com/v1beta/openai`,
`AI_MODEL=gemini-2.5-flash`, a real Google AI Studio API key required).
**Known risk, not yet verified live:** a Google AI Developer Forum report
describes issues combining `tool_call` deltas with streaming specifically
on Gemini's compat layer — `ChatService`'s tool-call loop depends on that
exact combination, so this should be exercised live (a message that
triggers a tool call) before relying on it, rather than assumed safe from
wire-format compatibility alone.

A **local [Ollama](https://ollama.com)** instance
(`AI_BASE_URL=http://localhost:11434/v1`, `AI_MODEL=llama3.1`,
`AI_API_KEY=ollama` — a non-secret placeholder since Ollama doesn't
enforce auth locally) and **[OpenRouter](https://openrouter.ai)**
(`AI_BASE_URL=https://openrouter.ai/api/v1`, `AI_MODEL=openai/gpt-4o` or
any other OpenRouter model slug) and real OpenAI all work the same way —
this project has run against all of them at different points without ever
touching `OpenAiCompatibleProvider`. Optional `AI_SITE_URL`/`AI_SITE_NAME`
config is sent as OpenRouter's `HTTP-Referer`/`X-Title` attribution
headers on chat requests when set; other providers ignore them.

Chat and embeddings can point at **different** providers: `AI_EMBEDDING_BASE_URL`/
`AI_EMBEDDING_API_KEY`/`AI_EMBEDDING_MODEL` default to the chat provider's
own values but can be set independently, since some providers' embedding-model
coverage is narrower than their chat coverage (OpenRouter notably) — a
common setup is chat via one provider with embeddings kept on another. The
same provider's `/embeddings` endpoint backs the Knowledge Base module
(§6.6); the current default is `gemini-embedding-001` (Google deprecated
`text-embedding-004`).

**Switching embedding models changes vector dimensionality** (e.g. OpenAI's
`text-embedding-3-small` is 1536-dim, Gemini's `gemini-embedding-001` is
configurable — commonly 768 or 1536-dim, Ollama's `nomic-embed-text` is
768-dim). `kb_document_chunks.embedding` is stored as `jsonb` rather than a
fixed-width column specifically so this requires no migration, but
documents already indexed under a different embedding model won't
cosine-compare meaningfully against new ones — re-upload existing
documents after switching embedding providers.

### 6.6 Knowledge Base — endpoint detail

| Method | Path | Action | Auth |
|---|---|---|---|
| GET | `/v1/knowledge-base/documents` | List uploaded documents | `knowledge_base.view` |
| POST | `/v1/knowledge-base/documents` | Upload a PDF (multipart, field `file`; optional `title`, defaults to the filename). Processing runs asynchronously | `knowledge_base.manage` |
| GET | `/v1/knowledge-base/documents/{id}` | Show one document, including `status`/`error_message`/`page_count` | `knowledge_base.view` |
| DELETE | `/v1/knowledge-base/documents/{id}` | Delete a document and its chunks | `knowledge_base.manage` |
| POST | `/v1/knowledge-base/ask` | Ask a question; retrieval-augmented answer with citations | `knowledge_base.view` |

`knowledge_base.view` (search/ask, list/view documents) is granted to
Owner, Admin, **and Member** by default — a company knowledge base is
usually meant for broad internal use. `knowledge_base.manage` (upload/
delete documents) stays Owner/Admin only.

**Pipeline.** Upload creates a `kb_documents` row (`status: processing`)
and dispatches a queued job: extract text per page (`smalot/pdfparser`,
text-extraction only — scanned/image-only PDFs won't OCR) → chunk each
page (fixed-size sliding window with overlap, `config('knowledge_base.
chunk_size')`/`chunk_overlap`, never crossing a page boundary) → embed
every chunk in one batch call → persist all chunks together → mark
`ready`. Any failure (including "no extractable text found") marks the
document `failed` with `error_message` set; a document is never left with
a partial chunk set.

**Retrieval.** `POST /v1/knowledge-base/ask` embeds the query and ranks
every chunk in the tenant's knowledge base by cosine similarity, computed
in PHP (`Domain\KnowledgeBase\VectorMath`) rather than a native pgvector
column — see DATABASE.md §4.8 for why. Brute-force, not an ANN index; fine
at typical internal-KB volumes. Top-K defaults to `config('knowledge_base.
top_k')` (5), overridable per request via `top_k`.

**Citation support.** Every answer's `citations` array includes, per
source chunk: `number` (matching the `[1]`, `[2]`, ... inline citation
markers the model is instructed to use), `document_id`, `title`,
`chunk_index`, `page_number`, a `snippet`, and the similarity `score`.

**Answer generation.** The retrieved chunks are assembled into a numbered-
context prompt instructing the model to answer only from that context and
cite it inline; the response includes `answer`, `citations`, and
`usage: {prompt_tokens, completion_tokens}`. An empty knowledge base
returns a fixed "I don't have any knowledge base content..." answer with
no citations, without calling the provider at all.

### 6.7 Automation Engine — endpoint detail

| Method | Path | Action | Auth |
|---|---|---|---|
| GET | `/v1/automation/workflows` | List workflows | `automation.view` |
| POST | `/v1/automation/workflows` | Create a workflow (starts as `draft` — activate separately) | `automation.manage` |
| GET | `/v1/automation/workflows/{id}` | Show one workflow | `automation.view` |
| DELETE | `/v1/automation/workflows/{id}` | Delete a workflow and its run history | `automation.manage` |
| GET | `/v1/automation/workflows/{id}/steps` | List a workflow's ordered steps | `automation.view` |
| POST | `/v1/automation/workflows/{id}/activate` | Activate — starts matching its trigger | `automation.manage` |
| POST | `/v1/automation/workflows/{id}/pause` | Pause — stops matching its trigger | `automation.manage` |
| GET | `/v1/automation/jobs` | List run history (`workflow_id`/`status` filters) | `automation.view` |
| GET | `/v1/automation/jobs/{id}` | Show one job run | `automation.view` |
| GET | `/v1/automation/jobs/{id}/steps` | Per-step audit trail of one run | `automation.view` |
| POST | `/v1/automation/jobs/{id}/retry` | Retry a failed job (resets attempts, re-dispatches) | `automation.manage` |

Unlike Ticketing/Knowledge Base, `automation.view`/`automation.manage` are
**Owner/Admin only** — workflow authoring can trigger emails and write
audit entries, so it's treated as an administrative capability, not
broad internal-user access.

**Workflow shape.** `POST /v1/automation/workflows` takes `name`,
optional `description`, and an ordered `steps` array: exactly one
`trigger` step first, then zero-or-more `condition` steps, then one-or-more
`action` steps.

```json
{
  "name": "Notify ops on critical ticket",
  "steps": [
    { "type": "trigger", "config": { "kind": "event", "event": "ticket.created" } },
    { "type": "condition", "config": { "field": "ticket.priority", "operator": "equals", "value": "critical" } },
    { "type": "action", "config": {
        "action": "send_notification", "to": "ops@example.com",
        "subject": "Critical ticket: {{ticket.subject}}",
        "message": "Ticket {{ticket.ticket_number}} was created with priority {{ticket.priority}}."
    } }
  ]
}
```

**Event-driven.** A single `AutomationEventSubscriber` matches every
trigger-event workflow against six events this codebase already fires —
`ticket.created`, `ticket.assigned`, `ticket.status_changed`,
`employee.created`, `employee.updated`, `employee.archived` — registered
via `Event::subscribe()` from the Automation module's own service
provider, with zero edits to the Ticketing/Employee modules themselves.
Each event is flattened into a context array (e.g.
`{"ticket": {"id": ..., "priority": "critical", ...}}`) that condition
evaluation and `{{placeholder}}` substitution both read from.

**Scheduled jobs.** A `kind: "schedule"` trigger takes a `cron`
expression instead of `event` (parsed via `dragonmantank/cron-expression`
— already a transitive Composer dependency of Laravel's own scheduler, so
no new package was needed). A `RunScheduledWorkflowsJob` checks every
active scheduled workflow each minute; `workflows.last_triggered_at`
de-dupes a workflow firing twice for the same due minute.

**Workflow execution.** A `condition` step evaluates its `field`/
`operator`/`value` against the run's context (`Domain\Automation\
ConditionEvaluator`; operators: `equals`, `not_equals`, `contains`,
`greater_than`, `less_than`). A condition that doesn't match short-circuits
the remaining steps as `skipped` — this is a successful "the automation
correctly did nothing" outcome, not a job failure.

**Actions.** An extensible `ActionRegistry` (same pattern as the AI
Assistant's tool registry) currently has two built-in actions:
`send_notification` (generic email via Laravel's on-demand notifications)
and `log_audit_event` (writes to the same shared `audit_logs` table every
other module uses). Both action configs support `{{field.path}}`
placeholder substitution from the trigger context.

**Retry handling.** Self-managed, not Laravel's `$tries`/`backoff()`:
`automation_jobs.attempts`/`max_attempts` is the single source of truth.
On a step failure, `ExecuteWorkflowJob` re-dispatches itself with an
increasing delay until `max_attempts` is reached, then marks the job
permanently `failed`. `POST /jobs/{id}/retry` additionally lets an
Owner/Admin manually reset and re-run a failed job on demand.

**Audit logs.** Every workflow/job lifecycle transition (workflow
created/activated/paused/deleted, job started/succeeded/failed, manual
retry) is recorded via the existing `AuditLogService`, reused as-is —
plus whatever a workflow's own `log_audit_event` actions add.

**Not built this round:** `integration_connections` (OAuth-based
third-party integrations) and inbound webhooks as a third trigger kind —
see DATABASE.md §3.7/§4.9.
