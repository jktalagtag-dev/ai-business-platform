# AI Business Platform

An enterprise, multi-tenant business platform. Backend is a Laravel 12 /
PostgreSQL API following Clean Architecture (Domain → Application →
Infrastructure → Http), the Repository Pattern, and a Service Layer —
see [ARCHITECTURE.md](ARCHITECTURE.md) for the full design rationale.

## Documentation

| Doc | Covers |
|---|---|
| [ARCHITECTURE.md](ARCHITECTURE.md) | System-wide architecture, folder structure, layering rules |
| [BACKEND.md](BACKEND.md) | Laravel backend detail: Controllers, Services, Repositories, Policies, Events, Queues |
| [FRONTEND.md](FRONTEND.md) | React/TypeScript frontend design |
| [DATABASE.md](DATABASE.md) | PostgreSQL schema, ERDs, indexes, migration order |
| [API.md](API.md) | REST conventions, response envelope, implemented endpoint catalogue |
| [CHANGELOG.md](CHANGELOG.md) | What's shipped, module by module |

## Modules implemented so far

- **Authentication & RBAC** — Sanctum token auth, database-driven roles
  and permissions (Owner/Admin/HR/Member), tenant provisioning on signup.
- **Inventory Management** — Products, Categories, Suppliers, and a
  Stock ledger (movements + cached on-hand quantity).
- **Employee Management** — Employee CRUD, Departments, Positions,
  Emergency Contacts, Notes, profile picture upload, department-manager-
  scoped visibility, and a granular audit trail.
- **IT Ticketing** — Ticket CRUD with system-generated ticket numbers,
  assignment/reassignment, status workflow, comments (with internal
  notes), attachments, dashboard statistics, SLA-breach escalation via a
  scheduled job, and an AI-ready-but-AI-free service architecture.
- **AI Assistant** — OpenAI-compatible chat (any compatible endpoint works
  by changing config alone) with streaming replies (SSE), conversation
  history, per-conversation system prompts, context-window memory,
  function calling (ticket statistics and knowledge base search tools
  reusing existing authorized Services), and token usage tracking.
- **Knowledge Base** — PDF upload with asynchronous processing (text
  extraction, chunking, embeddings), retrieval by cosine similarity, and
  cited answer generation (`/v1/knowledge-base/ask`) — also reachable from
  the AI Assistant chat itself via function calling.
- **Automation Engine** — event-driven and scheduled workflows (trigger →
  condition → action), an extensible action registry (send notification,
  log audit event), self-managed retry handling, and full run history —
  triggered by the six domain events Ticketing/Employee Management
  already fire, or by a cron schedule.

Every module shares the same generic audit log, tenant-scoping middleware,
API response envelope, and cursor pagination — see BACKEND.md for how a
new module plugs into this.

## Backend setup

Requirements: PHP 8.2+, Composer, PostgreSQL.

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

Edit `.env` and set your local PostgreSQL credentials (`DB_DATABASE`,
`DB_USERNAME`, `DB_PASSWORD`), then:

```bash
php artisan migrate --seed
php artisan serve
```

`--seed` runs `RolePermissionSeeder`, which provisions the system roles
(Owner, Admin, HR, Member) and the full permission catalogue — required
before any tenant can be meaningfully used.

To use the AI Assistant (and Knowledge Base, which reuses the same
provider for embeddings), set `AI_API_KEY` (or `OPENAI_API_KEY`) in
`.env`; `AI_BASE_URL`/`AI_MODEL`/`AI_EMBEDDING_MODEL` default to the real
OpenAI API but can point at any OpenAI-compatible endpoint instead. See
`config/ai.php` and `config/knowledge_base.php` (chunk size/overlap,
top-K, max upload size).

### Running tests

Tests run against SQLite in-memory (configured in `phpunit.xml`), so no
database setup is required to run the suite:

```bash
cd backend
./vendor/bin/pest
```

### API documentation

Once the server is running, interactive Swagger docs are served at
`/api/documentation`, generated from PHP attributes on the Controllers
(regenerate after changing an endpoint's annotations with
`php artisan l5-swagger:generate`).

## Frontend

Not yet implemented — see [FRONTEND.md](FRONTEND.md) for the planned
React + TypeScript + Vite design (React Router, TanStack Query, Tailwind).
