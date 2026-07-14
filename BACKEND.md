# Laravel Backend Design

Status: Draft v1.0 (design phase, no code written yet)
Related: [ARCHITECTURE.md](ARCHITECTURE.md) · [API.md](API.md) · [DATABASE.md](DATABASE.md)

This document details every backend layer (Controllers → Services →
Repositories → Policies → Events/Listeners → Queues/Jobs → Resources →
Middleware → Validation → Routes) against the 9 domains defined in
[DATABASE.md](DATABASE.md) §2. It follows the Clean Architecture layering
and rules already fixed in [ARCHITECTURE.md](ARCHITECTURE.md) §5 — this is
the concrete backend-instantiation of that architecture, not a
replacement for it.

## Domain Legend

Used throughout this document to keep tables compact:

| Code | Domain |
|---|---|
| `RBAC` | Identity & RBAC (tenants, users, roles, permissions) |
| `HR` | Departments & Employees |
| `INV` | Suppliers & Inventory |
| `SALES` | Sales |
| `TICK` | Tickets |
| `FILE` | Files |
| `AUDIT` | Audit Logs & Notifications |
| `AI` | AI, Knowledge Base & Embeddings |
| `AUTO` | Automation |

## 1. Folder Structure

Extends the layer skeleton from ARCHITECTURE.md §4.2 with every concrete
file this design calls for, grouped by domain sub-namespace within each
layer so a domain's full vertical slice is easy to locate.

```
backend/
├── app/
│   ├── Domain/
│   │   ├── Shared/{ValueObjects,Enums,Exceptions}/
│   │   ├── Rbac/{Tenant,User,Role,Permission}.php           # entities
│   │   ├── Hr/{Department,Employee}.php
│   │   ├── Inventory/{Supplier,Product,InventoryItem,PurchaseOrder}.php
│   │   ├── Sales/{Customer,SalesOrder,Payment}.php
│   │   ├── Tickets/{Ticket,TicketComment}.php
│   │   ├── Files/File.php
│   │   ├── Audit/{AuditLog,Notification}.php
│   │   ├── Ai/{Conversation,Document,KnowledgeBaseArticle,Embedding}.php
│   │   └── Automation/{Workflow,AutomationJob}.php
│   │
│   ├── Application/
│   │   ├── Contracts/
│   │   │   ├── Repositories/                # one interface per aggregate root (§4)
│   │   │   └── Services/                    # AIProviderInterface, StorageInterface, ...
│   │   ├── Services/
│   │   │   ├── Rbac/{AuthService,TenantService,MembershipService,RoleService,TokenService}.php
│   │   │   ├── Hr/{DepartmentService,EmployeeService}.php
│   │   │   ├── Inventory/{SupplierService,ProductService,InventoryService,PurchaseOrderService}.php
│   │   │   ├── Sales/{CustomerService,SalesOrderService,PaymentService}.php
│   │   │   ├── Tickets/{TicketService,TicketCommentService}.php
│   │   │   ├── Files/FileUploadService.php
│   │   │   ├── Audit/{AuditLogService,NotificationService}.php
│   │   │   ├── Ai/{ChatService,EmbeddingService,DocumentService,KnowledgeBaseService}.php
│   │   │   └── Automation/{WorkflowService,AutomationEngineService,IntegrationConnectionService}.php
│   │   ├── DTOs/<Domain>/...
│   │   ├── Events/<Domain>/...                # see §5
│   │   ├── Listeners/<Domain>/...             # see §6
│   │   ├── Jobs/<Domain>/...                  # see §8
│   │   └── Rules/                             # custom validation rules, see §11
│   │
│   ├── Infrastructure/
│   │   ├── Persistence/Eloquent/
│   │   │   ├── Models/<Domain>/...            # Eloquent models, 1:1 with DATABASE.md tables
│   │   │   └── Repositories/<Domain>/...      # implements Application/Contracts/Repositories
│   │   ├── AI/{Providers/{AnthropicProvider,OpenAIProvider},VectorStore/PgVectorStore}.php
│   │   ├── Automation/Engine/{ActionRegistry,Actions/*}.php
│   │   ├── Storage/{S3Disk,LocalDisk,VirusScanner}.php
│   │   └── ExternalServices/{PaymentProviderClient,SlackClient,...}.php
│   │
│   ├── Http/
│   │   ├── Controllers/Api/V1/<Domain>/...    # see §2
│   │   ├── Requests/<Domain>/...              # see §11
│   │   ├── Resources/<Domain>/...             # see §10
│   │   └── Middleware/...                     # see §9
│   │
│   ├── Policies/<Domain>/...                  # see §4
│   └── Providers/
│       ├── RepositoryServiceProvider.php      # binds contracts → Eloquent implementations
│       ├── AIServiceProvider.php              # binds AIProviderInterface → configured adapter
│       ├── AutomationServiceProvider.php
│       └── EventServiceProvider.php           # Event → Listener map, see §6
│
├── routes/
│   ├── api_v1.php                             # entrypoint, requires domain route files
│   ├── api/v1/{auth,rbac,hr,inventory,sales,tickets,files,notifications,ai,automation}.php
│   └── webhooks.php
├── database/{migrations,seeders,factories}/<Domain>/...
└── tests/{Unit,Feature,Integration}/<Domain>/...
```

## 2. Controllers

One controller per **aggregate root** (per API.md's "nested resources only
one level deep" rule — line-item/child tables like `sales_order_items`,
`purchase_order_items`, `workflow_steps`, `automation_job_steps`,
`role_permissions`, `document_chunks` have **no controller**; they're
managed as nested payloads/actions through their parent's Service). Every
controller method: validate via Form Request → call one Service method →
return a Resource. No business logic, no direct model/repository access.

| Domain | Controller | Notable non-CRUD actions |
|---|---|---|
| RBAC | `AuthController` | `login`, `logout`, `refresh` |
| RBAC | `TenantController` | `updateSettings` |
| RBAC | `TenantUserController` | `invite`, `accept`, `revoke` |
| RBAC | `RoleController` | `assignPermission`, `revokePermission` |
| RBAC | `PermissionController` | read-only (`index`) — system catalogue |
| RBAC | `UserPermissionOverrideController` | — |
| RBAC | `PersonalAccessTokenController` | `revoke` |
| HR | `DepartmentController` | `assignManager` |
| HR | `EmployeeController` | `changeDepartment`, `terminate` |
| INV | `SupplierController` | — |
| INV | `ProductCategoryController` | — |
| INV | `ProductController` | — |
| INV | `WarehouseController` | — |
| INV | `InventoryItemController` | `adjust` (creates an `inventory_movements` row) |
| INV | `InventoryMovementController` | read-only (`index`, filtered ledger) |
| INV | `PurchaseOrderController` | `submit`, `receive`, `cancel` |
| SALES | `CustomerController` | — |
| SALES | `SalesOrderController` | `confirm`, `fulfill`, `cancel` |
| SALES | `PaymentController` | read-only + `refund` |
| TICK | `TicketCategoryController` | — |
| TICK | `TicketController` | `assign`, `resolve`, `close`, `reopen` |
| TICK | `TicketCommentController` | nested: `/tickets/{id}/comments` |
| TICK | `TicketAttachmentController` | nested: `/tickets/{id}/attachments` |
| FILE | `FileUploadController` | `presign`, `confirm` |
| AUDIT | `AuditLogController` | read-only (`index`, admin-only) |
| AUDIT | `NotificationController` | `markRead`, `markAllRead` |
| AI | `AiConversationController` | nested `messages`, streaming `sendMessage` |
| AI | `KnowledgeBaseCategoryController` | — |
| AI | `KnowledgeBaseArticleController` | `publish`, `archive` |
| AUTO | `WorkflowController` | `activate`, `pause`, `duplicate` |
| AUTO | `AutomationJobController` | read-only + `retry` |
| AUTO | `IntegrationConnectionController` | `connect` (OAuth redirect), `disconnect` |
| — | `WebhookController` (unauthenticated, HMAC-verified) | one action per registered integration |

`embeddings` has no controller — it's populated as an internal side
effect of `DocumentService`/`KnowledgeBaseService` via `EmbeddingService`,
never written to directly through the API.

## 3. Repositories

One repository interface + Eloquent implementation per **aggregate
root** — the same boundary as Controllers (§2). Child/line-item rows are
loaded and persisted through their parent aggregate's repository
(e.g. `SalesOrderRepository::save()` persists `sales_order_items` as part
of saving the order), never independently.

| Domain | Repository interfaces |
|---|---|
| RBAC | `TenantRepositoryInterface`, `UserRepositoryInterface`, `TenantUserRepositoryInterface`, `RoleRepositoryInterface`, `PermissionRepositoryInterface`, `UserPermissionOverrideRepositoryInterface`, `PersonalAccessTokenRepositoryInterface` |
| HR | `DepartmentRepositoryInterface`, `EmployeeRepositoryInterface` |
| INV | `SupplierRepositoryInterface`, `ProductCategoryRepositoryInterface`, `ProductRepositoryInterface`, `WarehouseRepositoryInterface`, `InventoryItemRepositoryInterface`, `InventoryMovementRepositoryInterface`, `PurchaseOrderRepositoryInterface` |
| SALES | `CustomerRepositoryInterface`, `SalesOrderRepositoryInterface`, `PaymentRepositoryInterface` |
| TICK | `TicketCategoryRepositoryInterface`, `TicketRepositoryInterface`, `TicketCommentRepositoryInterface` |
| FILE | `FileRepositoryInterface` |
| AUDIT | `AuditLogRepositoryInterface`, `NotificationRepositoryInterface` |
| AI | `AiConversationRepositoryInterface`, `AiMessageRepositoryInterface`, `DocumentRepositoryInterface`, `KnowledgeBaseArticleRepositoryInterface`, `EmbeddingRepositoryInterface` |
| AUTO | `WorkflowRepositoryInterface`, `AutomationJobRepositoryInterface`, `IntegrationConnectionRepositoryInterface` |

Every implementation applies the tenant scope (ARCHITECTURE.md §5) and
returns Domain entities/DTOs, never raw Eloquent models, to the Service
layer. Reusable complex filters (e.g. "open tickets assigned to me",
"products below reorder point") are expressed as named Criteria/Specification
classes passed into a generic `find(Criteria $criteria)` rather than
piling ad-hoc methods onto each interface.

## 4. Policies

One policy per resource a user directly acts on. Standard abilities
(`viewAny`, `view`, `create`, `update`, `delete`) are present on every
policy; custom abilities are added only where a state transition needs
its own authorization rule distinct from `update`. Policies are invoked
from **Services**, not Controllers (ARCHITECTURE.md §8), so authorization
is enforced identically regardless of caller (HTTP, queued job, console
command).

| Domain | Policy | Custom abilities |
|---|---|---|
| RBAC | `TenantPolicy`, `TenantUserPolicy`, `RolePolicy` | `RolePolicy::assignPermission` |
| HR | `DepartmentPolicy`, `EmployeePolicy` | `EmployeePolicy::terminate`, `changeDepartment` |
| INV | `SupplierPolicy`, `ProductPolicy`, `WarehousePolicy`, `InventoryItemPolicy`, `PurchaseOrderPolicy` | `InventoryItemPolicy::adjust`, `PurchaseOrderPolicy::receive` |
| SALES | `CustomerPolicy`, `SalesOrderPolicy`, `PaymentPolicy` | `SalesOrderPolicy::confirm/fulfill/cancel`, `PaymentPolicy::refund` |
| TICK | `TicketPolicy`, `TicketCommentPolicy` | `TicketPolicy::assign`, `TicketPolicy::viewInternalComments` |
| FILE | `FilePolicy` | `download` |
| AUDIT | `AuditLogPolicy` (admin-only `viewAny`), `NotificationPolicy` | — |
| AI | `AiConversationPolicy`, `KnowledgeBaseArticlePolicy` | `KnowledgeBaseArticlePolicy::publish` |
| AUTO | `WorkflowPolicy`, `AutomationJobPolicy`, `IntegrationConnectionPolicy` | `WorkflowPolicy::activate/pause`, `AutomationJobPolicy::retry` |

Every policy's authorization check ultimately resolves against the RBAC
tables (`roles` → `role_permissions`, with `user_permission_overrides`
taking precedence) rather than hardcoded role-name checks — this is what
lets new roles/permissions be added by tenant admins without a deploy.

## 5. Events

Domain events are named in the past tense and dispatched from **Services**
after a state change is persisted (never from Controllers or Repositories).

| Domain | Events |
|---|---|
| RBAC | `UserInvited`, `UserJoinedTenant`, `RoleAssigned`, `RoleRevoked` |
| HR | `EmployeeHired`, `EmployeeTerminated`, `EmployeeDepartmentChanged` |
| INV | `StockLevelLow`, `InventoryAdjusted`, `PurchaseOrderReceived` |
| SALES | `SalesOrderConfirmed`, `SalesOrderFulfilled`, `SalesOrderCancelled`, `PaymentReceived`, `PaymentFailed` |
| TICK | `TicketCreated`, `TicketAssigned`, `TicketStatusChanged`, `TicketSlaBreached` |
| FILE | `FileUploaded`, `FileScanCompleted` |
| AI | `DocumentIngested`, `AiConversationCompleted`, `KnowledgeBaseArticlePublished` |
| AUTO | `WorkflowActivated`, `AutomationJobCompleted`, `AutomationJobFailed` |

These same events are also the trigger surface for the Automation engine
(ARCHITECTURE.md §10) — every event above is a candidate `workflow_steps`
trigger type, so this list should stay in sync with `docs/api/openapi.yaml`'s
documented trigger catalogue.

## 6. Listeners

Registered in `EventServiceProvider`. Business-logic side effects are
queued (so a slow notification/embedding call never blocks the request
that raised the event); logging/state-sync listeners that must be
consistent within the same transaction run sync.

| Event | Listener(s) | Sync/Queued | Queue |
|---|---|---|---|
| `UserInvited` | `SendInvitationEmail` | Queued | `notifications` |
| `UserJoinedTenant` | `ProvisionDefaultRole` | Sync | — |
| `StockLevelLow` | `CreateReorderAlertNotification` | Queued | `notifications` |
| `SalesOrderFulfilled` | `RecordInventoryDeduction` | Sync | — (must commit atomically with the order) |
| `TicketAssigned` | `SendTicketAssignedNotification` | Queued | `notifications` |
| `TicketSlaBreached` | `EscalateSlaBreach` | Queued | `notifications` |
| `FileUploaded` | `DispatchVirusScan` | Queued | `default` |
| `DocumentIngested` | `GenerateDocumentEmbeddings` | Queued | `ai` |
| `KnowledgeBaseArticlePublished` | `GenerateArticleEmbeddings` | Queued | `ai` |
| `AiConversationCompleted` | `RecordAiUsage` | Queued | `ai` |
| *(all events in §5)* | `AuditLogSubscriber` (Laravel event subscriber, not a single listener) | Queued | `default` |
| *(all events in §5)* | `AutomationTriggerSubscriber` — matches the event against active `workflow_steps` triggers for the tenant and dispatches `ExecuteWorkflowJob` per match | Sync dispatch, async execution | `automation` |

`AuditLogSubscriber` and `AutomationTriggerSubscriber` are the two
cross-cutting listeners that subscribe broadly rather than to one event —
implemented as Laravel Event Subscribers (`subscribe()` registering many
`Event → method` pairs) so this table doesn't need a per-event row for them.

## 7. Queues

Reuses the 4 queues fixed in ARCHITECTURE.md §11 (`default`, `ai`,
`automation`, `notifications`), each with its own Horizon-managed worker
pool so a burst in one workload can't starve another.

| Queue | Carries | Notes |
|---|---|---|
| `default` | Audit logging, file scanning, misc sync jobs | Baseline priority |
| `ai` | AI completions, embeddings generation | Rate-limited to respect provider quotas; longest-running |
| `automation` | Workflow execution (`ExecuteWorkflowJob`) | Isolated so a stuck third-party action doesn't delay other queues |
| `notifications` | Email/SMS/push/in-app delivery | Highest worker count — user-facing latency matters most here |

If upload volume grows enough that `ScanUploadedFileJob` measurably delays
other `default` jobs, splitting a dedicated `files` queue is the flagged
next step — not done preemptively (ARCHITECTURE.md §19-style open
decision, not a default).

## 8. Jobs

Laravel queued **Job classes** (`Application/Jobs`) — distinct from the
`automation_jobs` **database table** in DATABASE.md, which records
business-level workflow executions. `ExecuteWorkflowJob` is the bridge
between the two: it's the Job class that processes one `automation_jobs`
row.

| Job class | Queue | Triggered by | Purpose |
|---|---|---|---|
| `SendNotificationJob` | `notifications` | listeners in §6 | Channel-agnostic dispatch (in-app/email/SMS/push) |
| `GenerateAiCompletionJob` | `ai` | `ChatService::sendMessage` | Streams a completion, publishes tokens over Redis pub/sub |
| `GenerateEmbeddingJob` | `ai` | `DocumentIngested`, `KnowledgeBaseArticlePublished` | Chunks + embeds content into `embeddings` |
| `ScanUploadedFileJob` | `default` | `FileUploaded` | ClamAV scan, updates `files.scan_status` |
| `ExecuteWorkflowJob` | `automation` | `AutomationTriggerSubscriber` | Runs one `automation_jobs` row through its `workflow_steps`, recording `automation_job_steps` |
| `SyncPaymentStatusJob` | `default` | payment provider webhook | Reconciles `payments.status` with the provider |
| `RefreshIntegrationTokenJob` | `default` | scheduled (hourly) | Refreshes OAuth tokens nearing `integration_connections.expires_at` |
| `RebuildInventorySnapshotJob` | `default` | scheduled (nightly) | Reconciles `inventory_items.quantity_on_hand` against the `inventory_movements` ledger, corrects drift |
| `PurgeExpiredNotificationsJob` | `default` | scheduled (weekly) | Housekeeping per tenant retention policy |

Every Job declares explicit `$tries`/`backoff()` and a `failed()` handler
that updates the relevant domain row (e.g. marks `automation_job_steps.status = 'failed'`)
rather than leaving failure state only in Laravel's `failed_jobs` table
(ARCHITECTURE.md §11). External-side-effect jobs (`GenerateAiCompletionJob`,
`SyncPaymentStatusJob`, `ExecuteWorkflowJob` actions that call webhooks)
carry an idempotency key so a retry can't double-charge or double-send.

## 9. Middleware

| Middleware | Purpose | Applied to |
|---|---|---|
| `ResolveTenant` | Resolves subdomain/`X-Tenant-Id` header into `TenantContext` (ARCHITECTURE.md §8) | Global, all `/api/v1/*` except `webhooks.php` |
| `EnsureTenantMembership` | Confirms the authenticated user belongs to the resolved tenant | Global, all authenticated routes |
| `Authenticate` (Sanctum) | Session/token authentication | Global, all authenticated routes |
| `CheckAbility:{permission}` | Route-level permission check against RBAC (§4) before the Controller runs — first line of defense, Policy in the Service is the second | Per-route, e.g. `CheckAbility:workflows.write` |
| `IdempotencyMiddleware` | Handles `Idempotency-Key` header (API.md §4) | Mutating routes with external side effects (payments, workflow activation, webhooks dispatch) |
| `RateLimitByTenant` | Redis token-bucket, per-tenant + per-token; stricter limits on `/ai/*` | Global, tighter group for `ai.php` routes |
| `VerifyWebhookSignature` | HMAC verification of inbound webhook payloads | `webhooks.php` only |
| `ForceJsonResponse` | Normalizes `Accept`/`Content-Type` for API consistency | Global |
| `AttachRequestId` | Generates/propagates `request_id` used in the response envelope (API.md §4) and audit/log correlation | Global, runs first |

## 10. Resources

One `JsonResource` per entity actually exposed via the API, formatting
data into the envelope fixed in API.md §3 (`data.type`,
`data.attributes`) — Resources format only; they never query or contain
business logic.

| Domain | Resources |
|---|---|
| RBAC | `TenantResource`, `UserResource`, `TenantUserResource`, `RoleResource`, `PermissionResource` |
| HR | `DepartmentResource`, `EmployeeResource` |
| INV | `SupplierResource`, `ProductResource`, `WarehouseResource`, `InventoryItemResource`, `InventoryMovementResource`, `PurchaseOrderResource` (embeds `PurchaseOrderItemResource` as a nested array) |
| SALES | `CustomerResource`, `SalesOrderResource` (embeds `SalesOrderItemResource`), `PaymentResource` |
| TICK | `TicketResource`, `TicketCommentResource`, `TicketAttachmentResource` |
| FILE | `FileResource` |
| AUDIT | `AuditLogResource`, `NotificationResource` |
| AI | `AiConversationResource`, `AiMessageResource`, `DocumentResource`, `KnowledgeBaseArticleResource` (`embeddings` is never serialized to the API — internal only) |
| AUTO | `WorkflowResource` (embeds `WorkflowStepResource`), `AutomationJobResource` (embeds `AutomationJobStepResource`), `IntegrationConnectionResource` (never serializes `credentials`) |

List endpoints use Laravel's `AnonymousResourceCollection` with a custom
`paginationMeta()` macro producing the cursor `meta.pagination` block from
API.md §3.2 — no separate hand-written Collection class per resource.

## 11. Validation

One `FormRequest` per write operation:
`Store{Resource}Request` / `Update{Resource}Request` /
`{Action}{Resource}Request` for custom actions with their own payload
(e.g. `AdjustInventoryItemRequest`, `AssignTicketRequest`). Every
`authorize()` delegates to the matching Policy (§4) — validation classes
never re-implement authorization logic themselves.

Shared custom `Rule` classes (`Application/Rules`), reused across domains
instead of repeating `Rule::exists()->where(...)` boilerplate:

| Rule | Purpose |
|---|---|
| `ExistsInCurrentTenant` | Wraps `exists` scoped to the resolved tenant — prevents cross-tenant ID references (e.g. assigning a ticket to another tenant's employee) |
| `UniqueInCurrentTenant` | Wraps `unique` scoped to the resolved tenant (SKU, employee number, role name) |
| `SufficientStock` | Validates a `sales_order_items` quantity against `inventory_items.quantity_on_hand - quantity_reserved` |
| `ValidWorkflowStepConfig` | Validates `workflow_steps.config` JSON shape against the schema for its declared `type` |
| `ValidEmbeddableType` | Restricts `embeddings.embeddable_type` (internal use, not user-submitted, but shared by the services that write it) |

Representative examples (illustrative, not exhaustive — full rule sets
live with each Form Request at implementation time):

| Form Request | Key rules |
|---|---|
| `StoreProductRequest` | `sku`: required, `UniqueInCurrentTenant`; `category_id`: `ExistsInCurrentTenant` |
| `StoreSalesOrderRequest` | `items.*.quantity`: `SufficientStock`; `customer_id`: `ExistsInCurrentTenant` |
| `AssignTicketRequest` | `employee_id`: required, `ExistsInCurrentTenant`, must be active employment status |
| `StoreWorkflowStepRequest` | `type`: required, in (`trigger`,`condition`,`action`); `config`: `ValidWorkflowStepConfig` |
| `StoreEmployeeRequest` | `employee_number`: required, `UniqueInCurrentTenant`; `department_id`: nullable, `ExistsInCurrentTenant` |

## 12. Routes

`routes/api_v1.php` requires one file per domain, each wrapped in the
global middleware stack (`ResolveTenant`, `Authenticate`,
`EnsureTenantMembership`, `RateLimitByTenant`) plus per-route
`CheckAbility`. `routes/webhooks.php` is registered separately, outside
the tenant-session middleware group, protected instead by
`VerifyWebhookSignature`.

| File | Representative routes |
|---|---|
| `routes/api/v1/auth.php` | `POST /auth/login`, `POST /auth/logout`, `POST /auth/refresh` |
| `routes/api/v1/rbac.php` | `apiResource('tenant-users', ...)`, `apiResource('roles', ...)`, `POST /roles/{id}/permissions`, `GET /permissions`, `apiResource('tokens', ...)` |
| `routes/api/v1/hr.php` | `apiResource('departments', ...)`, `POST /departments/{id}/manager`, `apiResource('employees', ...)`, `POST /employees/{id}/terminate` |
| `routes/api/v1/inventory.php` | `apiResource('suppliers', ...)`, `apiResource('products', ...)`, `apiResource('warehouses', ...)`, `GET /inventory-items`, `POST /inventory-items/{id}/adjust`, `GET /inventory-movements`, `apiResource('purchase-orders', ...)`, `POST /purchase-orders/{id}/{submit,receive,cancel}` |
| `routes/api/v1/sales.php` | `apiResource('customers', ...)`, `apiResource('sales-orders', ...)`, `POST /sales-orders/{id}/{confirm,fulfill,cancel}`, `GET /payments`, `POST /payments/{id}/refund` |
| `routes/api/v1/tickets.php` | `apiResource('ticket-categories', ...)`, `apiResource('tickets', ...)`, `POST /tickets/{id}/{assign,resolve,close,reopen}`, `apiResource('tickets.comments', ...)`, `apiResource('tickets.attachments', ...)` |
| `routes/api/v1/files.php` | `POST /uploads/presign`, `POST /uploads/confirm` |
| `routes/api/v1/notifications.php` | `GET /notifications`, `POST /notifications/{id}/read`, `POST /notifications/read-all` |
| `routes/api/v1/audit.php` | `GET /audit-logs` (admin-only) |
| `routes/api/v1/ai.php` | `apiResource('ai/conversations', ...)`, `POST /ai/conversations/{id}/messages` (SSE-capable), `apiResource('knowledge-base/categories', ...)`, `apiResource('knowledge-base/articles', ...)`, `POST /knowledge-base/articles/{id}/publish` |
| `routes/api/v1/automation.php` | `apiResource('workflows', ...)`, `POST /workflows/{id}/{activate,pause,duplicate}`, `GET /automation-jobs`, `POST /automation-jobs/{id}/retry`, `apiResource('integrations', ...)`, `POST /integrations/{id}/connect`, `DELETE /integrations/{id}` |
| `routes/webhooks.php` | `POST /webhooks/{tenant}/{integration}` |

`apiResource(...)` denotes the standard `index/store/show/update/destroy`
set per API.md §2; only the non-CRUD actions are listed explicitly above.
Nested resources (`tickets.comments`, `tickets.attachments`) are limited
to one level per API.md's nesting rule.

## 13. Service Provider Wiring

- `RepositoryServiceProvider` — binds every interface in §3 to its
  Eloquent implementation. This is the **only** place a concrete
  repository class is referenced outside its own implementation and
  tests.
- `AIServiceProvider` — binds `AIProviderInterface` to the configured
  adapter (`AnthropicProvider` by default, tenant-overridable per
  ARCHITECTURE.md §9).
- `AutomationServiceProvider` — registers the `ActionRegistry` and its
  built-in actions (`SendEmailAction`, `CallWebhookAction`,
  `CreateRecordAction`, `TriggerAIPromptAction`).
- `EventServiceProvider` — the Event → Listener map from §6, plus
  registration of `AuditLogSubscriber` and `AutomationTriggerSubscriber`.

## 14. Open Decisions

- Whether `PaymentController`/`PaymentService` initiate provider charges
  directly or are strictly read/reconcile-only against a hosted checkout
  (recommended, keeps PCI scope off this backend entirely) — needs
  product sign-off before `StorePaymentRequest` is finalized.
- Whether a dedicated `files` queue is warranted at launch or deferred
  until upload volume data justifies it (§7).
- Exact SLA escalation policy behind `EscalateSlaBreach` (who gets
  notified, after how long) — currently a placeholder pending a support
  process decision.
