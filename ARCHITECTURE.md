# AI Business Platform — System Architecture

Status: Draft v1.0 (design phase, no code written yet)
Owner: Architecture
Related docs: [API.md](API.md) · [DATABASE.md](DATABASE.md)

## 1. Purpose & Scope

The AI Business Platform is a multi-tenant SaaS product that lets business
customers run AI-assisted workflows (chat/copilot features, document
intelligence, generated content) alongside no-code **automation** (trigger →
condition → action pipelines) on top of their own business data.

This document defines the enterprise architecture: how the backend, frontend,
AI layer, automation layer, and infrastructure fit together, and the
conventions every future change must follow.

## 2. Guiding Principles

These map directly to the project's engineering rules and are non-negotiable:

- **Clean Architecture** — dependencies point inward. Domain has zero
  knowledge of Laravel, HTTP, or the database. Infrastructure depends on
  Application/Domain contracts, never the reverse.
- **SOLID** throughout Services, Repositories, and AI provider adapters.
- **Repository Pattern** — all persistence access goes through repository
  interfaces defined in `Application/Contracts/Repositories`. Eloquent is an
  implementation detail behind those interfaces.
- **Service Layer** — all business logic lives in Application Services.
  Controllers, Jobs, and Console Commands only orchestrate calls into
  Services; they never contain business rules.
- **Thin controllers** — a controller validates the request (via Form
  Requests), calls exactly one Service method, and returns a Resource. No
  branching business logic in controllers.
- **No duplication** — shared behavior lives in Services, traits, or shared
  Domain value objects, not copy-pasted across modules.
- **Strict typing** — PHP: `declare(strict_types=1)`, typed properties,
  typed return values, no untyped `mixed` where avoidable. TypeScript:
  `strict: true`, no `any`.
- **REST conventions** and a **consistent API response envelope** (see
  [API.md](API.md)).
- **Schema changes require explicit approval** before migrations are
  written — this document proposes a target data model, not final
  migrations (see [DATABASE.md](DATABASE.md)).

## 3. High-Level System Diagram

```
                            ┌────────────────────────┐
                            │   Frontend (Next.js)    │
                            │  TypeScript SPA/SSR     │
                            └───────────┬─────────────┘
                                        │ HTTPS / REST + SSE (AI streaming)
                                        ▼
                         ┌───────────────────────────────┐
                         │        API Gateway / LB        │
                         └───────────────┬────────────────┘
                                         ▼
                         ┌───────────────────────────────┐
                         │     Laravel API (stateless)    │
                         │  Http → Application → Domain   │
                         │        → Infrastructure        │
                         └───┬───────┬───────┬───────┬────┘
                             │       │       │       │
                 ┌───────────┘   ┌───┘    ┌──┘    ┌──┘
                 ▼               ▼        ▼       ▼
         ┌──────────────┐ ┌───────────┐ ┌─────┐ ┌──────────────┐
         │ PostgreSQL   │ │ Redis      │ │ S3  │ │ AI Providers │
         │ (primary DB, │ │ (cache,    │ │(file│ │ Anthropic /  │
         │ + pgvector)  │ │ queue,     │ │store│ │ OpenAI, etc. │
         │              │ │ pub/sub)   │ │)    │ │ via adapter  │
         └──────────────┘ └─────┬──────┘ └─────┘ └──────────────┘
                                 ▼
                       ┌───────────────────┐
                       │  Queue Workers     │
                       │  (Horizon)         │
                       │  AI jobs, Automation│
                       │  jobs, Notifications│
                       └───────────────────┘
```

## 4. Folder Structure

### 4.1 Repository root

```
ai-business-platform/
├── backend/              # Laravel API (PHP 8.2+)
├── frontend/             # Next.js + TypeScript
├── docs/
│   ├── architecture/     # ADRs, diagrams, deep dives
│   ├── api/               # OpenAPI spec, endpoint contracts
│   ├── database/          # ERDs, schema change proposals
│   ├── images/
│   └── meetings/
├── scripts/               # dev/deploy helper scripts
├── assets/
├── .github/workflows/     # CI/CD pipelines
├── ARCHITECTURE.md
├── API.md
├── DATABASE.md
├── CONTRIBUTING.md
├── CHANGELOG.md
└── ROADMAP.md
```

### 4.2 Backend (`backend/`) — Clean Architecture layers

```
backend/
├── app/
│   ├── Domain/                        # Enterprise business rules (framework-free)
│   │   ├── Shared/
│   │   │   ├── ValueObjects/          # Money, TenantId, Email, ...
│   │   │   ├── Enums/
│   │   │   └── Exceptions/
│   │   ├── User/
│   │   ├── Tenant/
│   │   ├── Billing/
│   │   ├── Automation/                # Workflow, Trigger, Action, Condition entities
│   │   └── AI/                        # Conversation, Prompt, Completion entities
│   │
│   ├── Application/                   # Use cases — orchestrates Domain
│   │   ├── Contracts/
│   │   │   ├── Repositories/          # e.g. UserRepositoryInterface
│   │   │   └── Services/              # e.g. AIProviderInterface
│   │   ├── Services/                  # Service Layer — business logic
│   │   │   ├── User/UserService.php
│   │   │   ├── AI/ChatService.php
│   │   │   ├── AI/EmbeddingService.php
│   │   │   ├── Automation/WorkflowService.php
│   │   │   └── Billing/SubscriptionService.php
│   │   ├── DTOs/                      # Data transfer objects between layers
│   │   └── Jobs/                      # Queued use-case orchestration
│   │
│   ├── Infrastructure/                # Frameworks & drivers (implements contracts)
│   │   ├── Persistence/Eloquent/
│   │   │   ├── Models/
│   │   │   └── Repositories/          # Repository Pattern implementations
│   │   ├── AI/
│   │   │   ├── Providers/             # AnthropicProvider, OpenAIProvider
│   │   │   └── VectorStore/           # PgVectorStore
│   │   ├── Automation/Engine/         # Trigger listeners, action executors
│   │   ├── Storage/                   # S3Disk, LocalDisk adapters
│   │   └── ExternalServices/          # 3rd-party API clients (CRM, email, etc.)
│   │
│   ├── Http/                          # Presentation layer — thin
│   │   ├── Controllers/Api/V1/
│   │   ├── Requests/                  # Form Request validation
│   │   ├── Resources/                 # API Resource transformers
│   │   └── Middleware/
│   │
│   └── Providers/                     # DI bindings: interface → implementation
│
├── routes/api_v1.php
├── database/{migrations,seeders,factories}
├── config/
└── tests/{Unit,Feature,Integration}
```

**Dependency rule:** `Http → Application → Domain`, and
`Infrastructure → Application contracts`. Domain never imports from
Application, Infrastructure, or Http. Service Providers are the only place
that wires an `Infrastructure` implementation to an `Application` contract.

### 4.3 Frontend (`frontend/`) — TypeScript

Full folder structure, routing, and every frontend layer (pages, layouts,
components, hooks, services, forms, tables, charts, theming, responsive
design) is detailed in [FRONTEND.md](FRONTEND.md). Summary: Vite + React +
TypeScript SPA, React Router for routing, TanStack Query for server state,
Zustand only for genuine global client state, Tailwind for styling.
Cross-domain sharing happens only through `components/ui`, `lib`, and
`types`; each `modules/<domain>` stays a self-contained vertical slice.

## 5. Backend Architecture (Laravel, PHP 8.2+)

- **Layering**: Domain → Application → Infrastructure → Http, per §4.2.
- **Dependency Injection**: every Service depends on interfaces
  (`Application/Contracts`), bound to concrete `Infrastructure` classes in
  dedicated Service Providers (`AIServiceProvider`,
  `RepositoryServiceProvider`, `AutomationServiceProvider`). This keeps
  Services swappable and testable (mock the interface in unit tests).
- **Controllers**: one action per route (single-action or resource
  controllers), inject a Service via constructor, validate via Form
  Request, return an API Resource. No `DB::`, no `Model::` calls in
  controllers.
- **Validation**: all input validation lives in `Http/Requests`
  (`FormRequest` classes), never inline in controllers.
- **Errors**: domain-level failures throw typed exceptions
  (`Domain/Shared/Exceptions`), caught by a central `Handler` that maps them
  to the standard API error envelope (see [API.md](API.md)).
- **Multi-tenancy**: row-level tenant scoping. Every tenant-owned Eloquent
  model uses a global `TenantScope` that filters by `tenant_id` from the
  authenticated context. Repository implementations never bypass this
  scope. (See [DATABASE.md](DATABASE.md) §2.)

## 6. Frontend Architecture (TypeScript, React)

Supersedes the earlier Next.js proposal — the confirmed stack is a Vite +
React + TypeScript SPA with React Router, TanStack Query, and Tailwind.
Full detail (folder structure, pages, layouts, components, hooks,
services, forms, tables, charts, theme, responsive design) lives in
[FRONTEND.md](FRONTEND.md). Summary:

- **Framework**: Vite + React 18+, TypeScript strict mode, no `any`.
- **Routing**: React Router (data router), route-based code splitting.
- **Data fetching**: TanStack Query wraps the typed `api-client`; each
  `modules/<domain>/services` exposes typed functions
  (e.g. `getConversations(): Promise<Conversation[]>`) that Query hooks call.
  This is the frontend's own thin "repository" layer for remote data.
- **State**: server state lives in TanStack Query's cache; only genuinely
  global client state (auth session, UI preferences, active tenant) lives in
  a small Zustand store.
- **Types**: request/response types are generated from the backend's
  OpenAPI spec (see [API.md](API.md) §5) so frontend and backend never drift.
- **Streaming AI responses**: consumed via Server-Sent Events (SSE) or the
  Fetch streaming API, rendered incrementally in the chat/assistant UI.
- **Styling**: Tailwind CSS with a design-token theme; `components/ui`
  holds presentational, stateless components only, feature behavior stays
  in `modules/*`.
- **Testing**: Vitest + Testing Library for unit/component tests, Playwright
  for E2E.

## 7. API Structure

Full conventions, endpoint catalogue, versioning, and response envelope are
defined in [API.md](API.md). Summary:

- REST, resource-oriented, versioned under `/api/v1`.
- One consistent success/error envelope for every endpoint.
- Cursor-based pagination for large collections.
- OpenAPI 3.1 spec is the source of truth, generated types feed the frontend.

## 8. Authentication & Authorization

- **Mechanism**: Laravel Sanctum for first-party SPA session/token auth
  (frontend and backend share the registrable domain — cookie-based SPA
  auth, CSRF-protected). Sanctum personal access tokens for
  server-to-server / third-party API integrations.
- **Multi-tenancy identity**: a user belongs to one or more tenants
  (`tenant_users` pivot with a `role`). The active tenant is resolved per
  request (subdomain or header) and stored in a request-scoped
  `TenantContext`, which the `TenantScope` (see §5) reads from.
- **Authorization**: Laravel Policies + Gates for resource-level checks
  (e.g. `WorkflowPolicy::update`), backed by role/permission data
  (`roles`, `permissions`, `role_permissions` — RBAC). Policies are called
  from Services, not Controllers, so authorization logic is testable and
  reusable outside HTTP.
- **OAuth2 / third-party integrations** (e.g. connecting a customer's
  Google Workspace or Slack for automation triggers): Laravel Socialite or
  direct OAuth2 client per provider, tokens encrypted at rest
  (`encrypted` Eloquent cast), refreshed by a scheduled job.
- **API tokens for external consumers**: scoped Sanctum tokens with
  ability lists (`['workflows:read', 'workflows:write']`), rate-limited
  independently from session auth.

## 9. AI Layer

Goal: swap AI vendors without touching business logic, and give every
AI-backed feature consistent cost/usage tracking and guardrails.

```
Application/Services/AI/ChatService, EmbeddingService
        │  depends on
        ▼
Application/Contracts/Services/AIProviderInterface
        │  implemented by
        ▼
Infrastructure/AI/Providers/{AnthropicProvider, OpenAIProvider, ...}
```

- **Provider abstraction**: `AIProviderInterface` defines
  `complete()`, `stream()`, `embed()`. Concrete adapters translate to each
  vendor's SDK/API. Default provider is configurable per tenant (config +
  DB override), enabling model choice per customer plan.
- **Prompt management**: prompt templates are versioned records (see
  [DATABASE.md](DATABASE.md) `prompt_templates`), not hardcoded strings, so
  they can be iterated on without a deploy and A/B tested.
- **RAG / retrieval**: document chunks are embedded and stored in
  PostgreSQL via the `pgvector` extension (`document_chunks.embedding`).
  `EmbeddingService` handles chunking + embedding on upload;
  `ChatService` performs similarity search before calling the LLM.
- **Streaming**: long-running completions are dispatched as queued jobs
  that publish partial tokens over a Redis pub/sub channel, relayed to the
  frontend via SSE — keeps HTTP workers non-blocking under load.
- **Usage & cost tracking**: every AI call records tokens
  in/out, latency, and cost against the tenant (`ai_usage_logs`), feeding
  billing and per-tenant quota enforcement.
- **Guardrails**: input/output moderation hook in `ChatService` (provider
  moderation endpoint or a rules-based filter), configurable per tenant;
  PII redaction option before sending user data to a third-party provider.

## 10. Automation Layer

A trigger → condition → action workflow engine, so tenants can automate
business processes without code.

- **Domain model**: `Workflow` has many `WorkflowSteps`; each step is a
  `Trigger`, `Condition`, or `Action` (see [DATABASE.md](DATABASE.md)).
- **Event-driven core**: internal domain events (`UserInvited`,
  `DocumentUploaded`, `AIConversationCompleted`, incoming webhook events)
  are dispatched via Laravel's event system. A `WorkflowEventListener`
  matches events against active workflow triggers for the tenant.
- **Execution**: matched workflows are dispatched as queued jobs
  (`ExecuteWorkflowJob`) on the `automation` queue, so a slow third-party
  action never blocks the request cycle. Each step's result is persisted
  (`workflow_runs`, `workflow_step_runs`) for auditability and retry.
- **Actions**: implemented as small, single-purpose classes behind an
  `ActionInterface` (`SendEmailAction`, `CallWebhookAction`,
  `CreateRecordAction`, `TriggerAIPromptAction`), registered in an
  `ActionRegistry` so new action types are additive, not a rewrite
  (Open/Closed Principle).
- **External triggers**: inbound webhooks land on a dedicated, unauthenticated-but-signed
  endpoint (`/api/v1/webhooks/{tenant}/{integration}`), verified by
  HMAC signature, then translated into a domain event.

## 11. Queue System

- **Driver**: Redis, managed by **Laravel Horizon** for monitoring,
  auto-scaling workers, and metrics.
- **Queue segmentation** by priority/workload, each with its own worker
  pool so a burst of AI jobs can't starve notification delivery:
  - `default` — general application jobs
  - `ai` — LLM completions, embeddings (longer-running, rate-limited to
    respect provider quotas)
  - `automation` — workflow execution
  - `notifications` — email/SMS/push
- **Reliability**: typed Job classes with explicit `$tries`, `backoff()`
  (exponential), and `failed()` handlers that record failures for the
  relevant domain (e.g. mark a `workflow_step_run` as `failed`, not just
  the raw job). Exhausted retries land in `failed_jobs` and raise an alert.
- **Idempotency**: jobs that call external systems (AI providers, webhooks)
  carry an idempotency key so a retry can't double-charge or double-send.

## 12. Service Layer

- Every use case is a method on an Application Service
  (`ChatService::sendMessage()`, `WorkflowService::activate()`).
- Services depend only on `Application/Contracts` interfaces (repositories,
  AI provider, notifier) — never on Eloquent models or HTTP concerns
  directly.
- Services return DTOs or Domain entities, never Eloquent models, so the
  Http layer (Resources) and any future consumer (CLI, queued job, GraphQL
  later) can reuse the same Service unchanged.
- Cross-cutting concerns (authorization checks, tenant scoping assertions,
  usage-quota checks) are enforced inside the Service, so every caller gets
  them for free — not duplicated per Controller.

## 13. Repository Layer

- Interfaces live in `Application/Contracts/Repositories`
  (`UserRepositoryInterface`, `WorkflowRepositoryInterface`, ...), each
  scoped to one aggregate root.
- Implementations live in `Infrastructure/Persistence/Eloquent/Repositories`
  and are the only place Eloquent query builders are used.
- Complex, reusable filtering uses a lightweight Criteria/Specification
  pattern rather than ad-hoc query methods proliferating on the interface.
- Repositories return Domain entities or DTOs (mapped from Eloquent
  models), keeping persistence details out of the Application layer.
- This boundary is what makes Services unit-testable without a database
  (mock the repository interface) and makes swapping persistence
  technology (e.g. adding a read-replica-aware repository) a
  single-class change.

## 14. File Uploads

- **Storage abstraction**: Laravel Filesystem, `s3` disk in
  staging/production (AWS S3 or S3-compatible, e.g. MinIO for local dev),
  `local` disk only for local development.
- **Flow**: client requests a pre-signed upload URL from the API
  (`POST /api/v1/uploads/presign`) and uploads directly to object storage,
  keeping large files off the Laravel workers. The API then receives a
  confirmation callback/webhook to record the `file` entity.
- **Validation**: MIME type, extension, and size limits enforced both
  client-side (fast feedback) and server-side (authoritative) before a
  presigned URL is issued.
- **Security**: uploaded files are scanned asynchronously (ClamAV via a
  queued job) before being marked `available`; infected files are
  quarantined. Downloads are served via short-lived signed URLs, never
  public bucket paths.
- **Large files**: chunked/resumable upload (tus protocol) supported for
  document sets used in AI ingestion (§9 RAG pipeline).

## 15. Deployment

- **Containerization**: every service (`api`, `queue-worker`, `scheduler`,
  `frontend`) ships as its own Docker image from a shared base, built in
  CI. `docker-compose.yml` reproduces the full stack for local development.
- **Environments**: `local → staging → production`, each with isolated
  database, Redis, and storage bucket. Config via environment variables
  only (12-factor), never committed secrets.
- **Runtime**: container orchestration on Kubernetes (or ECS Fargate as a
  lighter-weight equivalent) — `api` and `frontend` are stateless
  deployments behind a load balancer/ingress with HPA (horizontal pod
  autoscaling); `queue-worker` scales on queue depth (via Horizon metrics);
  `scheduler` runs as a single-replica CronJob (Laravel's scheduler tick).
- **Data stores**: managed PostgreSQL (e.g. RDS/Cloud SQL) with automated
  backups and point-in-time recovery; managed Redis (e.g. ElastiCache).
- **Zero-downtime deploys**: rolling updates with readiness probes; DB
  migrations run as a separate pre-deploy step (`migrate --force`) gated
  by a health check before traffic shifts to the new revision.
- **Frontend delivery**: static assets on a CDN; SSR pages served from the
  containerized Next.js instance behind the same ingress.

## 16. CI/CD

- **Platform**: GitHub Actions (`.github/workflows/`).
- **Branching**: trunk-based with short-lived feature branches, PRs
  required into `main`; `main` auto-deploys to staging, tagged releases
  deploy to production after manual approval.
- **Pipeline stages** (fail fast, each gates the next):
  1. Lint — PHP: Pint/PHPCS · TS: ESLint + Prettier
  2. Static analysis — PHP: PHPStan (level ≥ 8) · TS: `tsc --noEmit` (strict)
  3. Unit tests — Pest/PHPUnit (backend) · Vitest (frontend)
  4. Feature/integration tests — Laravel feature tests against a real
     Postgres service container; frontend integration tests
  5. Build — Docker images for `api`, `frontend`, `queue-worker`
  6. Security scan — dependency audit (Composer/`npm audit`), container
     image scan
  7. Deploy to staging (automatic on `main`) → smoke tests → manual
     approval gate → deploy to production
- **Quality gates**: PR blocked on any failed stage, minimum coverage
  threshold on Services/Domain (see §17), and required review approval.

## 17. Testing Strategy

Test pyramid, weighted toward fast, isolated tests:

| Layer | Backend | Frontend | Notes |
|---|---|---|---|
| Unit | Pest/PHPUnit on Domain + Application Services, repositories mocked via interfaces | Vitest on hooks/utils/components | Fast, no DB/network, run on every commit |
| Feature/Integration | Laravel feature tests hitting real routes + test DB (RefreshDatabase) | Testing Library, mocked API layer | Verifies wiring: routing, validation, auth, DB |
| Contract | OpenAPI schema validated against actual responses | Generated types checked against OpenAPI | Prevents frontend/backend drift |
| E2E | — | Playwright against a full staging-like stack | Golden paths: auth, chat, workflow run, billing |

- **Coverage target**: ≥ 80% on `Domain` and `Application/Services`
  (business logic); Infrastructure and Http are covered mainly by
  Feature tests, not line-coverage targets.
- **Test doubles**: AI provider calls are mocked/faked in all non-E2E
  tests (no live API calls in CI) via a `FakeAIProvider` implementing
  `AIProviderInterface`.
- **Database**: tests run against a real Postgres (via CI service
  container / Testcontainers), not SQLite, so `pgvector` and JSONB
  behavior match production.

## 18. Scalability & Reliability

- **Stateless API**: no in-memory session state on `api` pods — horizontal
  autoscaling behind the load balancer is safe by default.
- **Caching**: Redis for query/result caching (tagged cache per tenant,
  invalidated on write via the Repository layer), and for rate-limiting
  counters.
- **Database**: read replicas for reporting/heavy-read endpoints; write
  traffic stays on the primary. Indexing strategy and partitioning
  candidates are detailed in [DATABASE.md](DATABASE.md).
- **Queue scaling**: Horizon auto-balances workers across the segmented
  queues (§11) based on queue depth, so an AI usage spike doesn't delay
  notification jobs.
- **Rate limiting**: per-tenant and per-token limits enforced at the API
  gateway/middleware layer using Redis token-bucket, with distinct limits
  for AI-backed endpoints (which carry real provider cost).
- **Observability**: structured JSON logging shipped to a centralized
  store (e.g. Loki/ELK); metrics via OpenTelemetry → Prometheus/Grafana;
  distributed tracing across API → queue job → AI provider call to debug
  latency in the AI/automation pipelines.
- **Multi-tenant isolation**: enforced at the data layer (§5, §8) rather
  than relying on application code discipline alone — the `TenantScope`
  is applied globally, and a defense-in-depth policy check happens again
  in each Service.

## 19. Open Decisions (require explicit sign-off before implementation)

- Kubernetes vs. ECS Fargate for orchestration (cost vs. operational
  complexity trade-off).
- Whether large enterprise tenants get schema-per-tenant isolation instead
  of row-level scoping (see [DATABASE.md](DATABASE.md) §2).
- Which AI providers are supported at launch beyond the default adapter.
- Build vs. embed for the automation engine (custom, as designed above,
  vs. adopting Temporal/n8n as an embedded dependency) — custom is
  recommended for tighter tenant/billing integration, but is more to
  maintain.

Any actual database schema changes derived from this document require
explicit approval per project rules before migrations are written.
