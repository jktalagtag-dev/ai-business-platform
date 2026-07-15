<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Application\DTOs\Employee\CreateDepartmentData;
use App\Application\DTOs\Employee\CreateEmployeeData;
use App\Application\DTOs\Employee\CreatePositionData;
use App\Application\DTOs\Inventory\AdjustStockData;
use App\Application\DTOs\Inventory\CreateCategoryData;
use App\Application\DTOs\Inventory\CreateProductData;
use App\Application\DTOs\Inventory\CreateSupplierData;
use App\Application\DTOs\Rbac\RegisterData;
use App\Application\DTOs\Ticket\CreateTicketCommentData;
use App\Application\DTOs\Ticket\CreateTicketData;
use App\Application\Services\Automation\WorkflowService;
use App\Application\Services\Employee\DepartmentService;
use App\Application\Services\Employee\EmployeeService;
use App\Application\Services\Employee\PositionService;
use App\Application\Services\Inventory\CategoryService;
use App\Application\Services\Inventory\InventoryItemService;
use App\Application\Services\Inventory\ProductService;
use App\Application\Services\Inventory\SupplierService;
use App\Application\Services\Rbac\AuthService;
use App\Application\Services\Ticket\TicketCommentService;
use App\Application\Services\Ticket\TicketService;
use App\Domain\Employee\EmergencyContact;
use App\Http\Support\RequestTenantContext;
use App\Infrastructure\Persistence\Eloquent\Models\Ai\AiConversation;
use App\Infrastructure\Persistence\Eloquent\Models\Ai\AiMessage;
use App\Infrastructure\Persistence\Eloquent\Models\KnowledgeBase\Document;
use App\Infrastructure\Persistence\Eloquent\Models\KnowledgeBase\DocumentChunk;
use App\Infrastructure\Persistence\Eloquent\Models\Tenant;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

/**
 * Populates a dedicated "Demo Company" tenant with realistic, browsable data
 * (15+ rows per module) via the real Application Services — so ticket/
 * employee numbering, auto-provisioned stock records, and Audit Log entries
 * are all generated exactly like real usage would produce. Idempotent: a
 * second run is a no-op if the demo tenant already exists (delete it first
 * to reseed from scratch).
 *
 * AI conversations/messages and Knowledge Base documents/chunks are the one
 * exception — inserted directly via Eloquent, since seeding them "for real"
 * would require a working LLM API key and real PDF files respectively.
 */
final class DemoDataSeeder extends Seeder
{
    public const DEMO_EMAIL = 'demo@example.com';

    public const DEMO_PASSWORD = 'password123';

    private const DEMO_TENANT_SLUG_HINT = 'demo-company';

    public function run(): void
    {
        if (Tenant::where('name', 'Demo Company')->exists()) {
            $this->command?->info('Demo tenant already exists — skipping DemoDataSeeder.');

            return;
        }

        [$owner, $tenantId] = $this->seedTenant();

        $departmentIds = $this->seedDepartments($owner);
        $positionIds = $this->seedPositions($owner);
        $employeeIds = $this->seedEmployees($owner, $tenantId, $departmentIds, $positionIds);

        $this->seedInventory($owner);
        $this->seedSuppliers($owner);

        $this->seedWorkflows($owner);
        $this->seedTickets($owner, $employeeIds);

        $this->drainQueue();

        $this->seedAiConversations($tenantId, $owner);
        $this->seedKnowledgeBase($tenantId, $owner);

        $this->command?->info('Demo data seeded. Login: '.self::DEMO_EMAIL.' / '.self::DEMO_PASSWORD);
    }

    /**
     * @return array{0: Authenticatable, 1: string}
     */
    private function seedTenant(): array
    {
        $auth = app(AuthService::class);

        $result = $auth->register(new RegisterData(
            name: 'Demo Owner',
            email: self::DEMO_EMAIL,
            password: self::DEMO_PASSWORD,
            tenantName: 'Demo Company',
        ));

        /** @var Authenticatable&User $owner */
        $owner = $result['user'];
        $tenantId = $result['membership']->tenantId;

        app(RequestTenantContext::class)->setTenantId($tenantId);

        // Policies check the *current Sanctum token's* abilities
        // (AuthorizesViaTokenAbilities), not the user/role directly — there's
        // no HTTP request here to populate that via middleware, so attach the
        // real token AuthService::register() just issued (it already carries
        // every permission key the Owner role grants).
        $owner->withAccessToken($owner->tokens()->latest('id')->first());

        return [$owner, $tenantId];
    }

    /**
     * @return list<string>
     */
    private function seedDepartments(Authenticatable $owner): array
    {
        $service = app(DepartmentService::class);

        $names = [
            'Engineering', 'Product', 'Design', 'Marketing', 'Sales',
            'Customer Support', 'Human Resources', 'Finance', 'Legal',
            'IT Operations', 'Quality Assurance', 'Data & Analytics',
            'Business Development', 'Facilities', 'Executive',
        ];

        $ids = [];
        foreach ($names as $name) {
            $department = $service->create($owner, new CreateDepartmentData(
                name: $name,
                description: "The {$name} team.",
                parentDepartmentId: null,
                managerEmployeeId: null,
            ));
            $ids[] = $department->id;
        }

        return $ids;
    }

    /**
     * @return list<string>
     */
    private function seedPositions(Authenticatable $owner): array
    {
        $service = app(PositionService::class);

        $titles = [
            'Software Engineer', 'Senior Software Engineer', 'Engineering Manager',
            'Product Manager', 'UX Designer', 'UI Designer', 'Marketing Specialist',
            'Sales Representative', 'Account Executive', 'Customer Support Specialist',
            'HR Generalist', 'Financial Analyst', 'IT Administrator',
            'QA Engineer', 'Data Analyst',
        ];

        $ids = [];
        foreach ($titles as $title) {
            $position = $service->create($owner, new CreatePositionData(
                title: $title,
                description: "Responsible for {$title} duties.",
            ));
            $ids[] = $position->id;
        }

        return $ids;
    }

    /**
     * @param  list<string>  $departmentIds
     * @param  list<string>  $positionIds
     * @return list<string>
     */
    private function seedEmployees(Authenticatable $owner, string $tenantId, array $departmentIds, array $positionIds): array
    {
        $service = app(EmployeeService::class);
        $faker = fake();

        $employmentTypes = ['full_time', 'full_time', 'full_time', 'full_time', 'part_time', 'contractor', 'intern'];
        $ids = [];

        // First employee is linked to the Owner's own login, so "My Profile"
        // and "create a ticket for myself" both work out of the box.
        $ownerEmployee = $service->create($owner, new CreateEmployeeData(
            userId: $owner->getAuthIdentifier(),
            firstName: 'Demo',
            lastName: 'Owner',
            email: self::DEMO_EMAIL,
            phone: $faker->phoneNumber(),
            departmentId: $departmentIds[14], // Executive
            positionId: $positionIds[2], // Engineering Manager
            managerEmployeeId: null,
            employmentType: 'full_time',
            employmentStatus: 'active',
            hireDate: '2022-01-10',
            address: [
                'line1' => $faker->streetAddress(),
                'city' => $faker->city(),
                'state' => $faker->stateAbbr(),
                'postal_code' => $faker->postcode(),
                'country' => 'US',
            ],
            emergencyContact: new EmergencyContact(
                name: $faker->name(),
                relationship: 'Spouse',
                phone: $faker->phoneNumber(),
            ),
            bio: 'Founder and owner of Demo Company.',
        ));
        $ids[] = $ownerEmployee->id;

        for ($i = 0; $i < 17; $i++) {
            $employee = $service->create($owner, new CreateEmployeeData(
                userId: null,
                firstName: $faker->firstName(),
                lastName: $faker->lastName(),
                email: $faker->unique()->safeEmail(),
                phone: $faker->phoneNumber(),
                departmentId: $departmentIds[$i % count($departmentIds)],
                positionId: $positionIds[$i % count($positionIds)],
                managerEmployeeId: $i > 0 && $i % 4 === 0 ? $ids[0] : null,
                employmentType: $employmentTypes[$i % count($employmentTypes)],
                employmentStatus: $i === 5 ? 'on_leave' : 'active',
                hireDate: $faker->dateTimeBetween('-3 years', '-1 month')->format('Y-m-d'),
                address: [
                    'line1' => $faker->streetAddress(),
                    'city' => $faker->city(),
                    'state' => $faker->stateAbbr(),
                    'postal_code' => $faker->postcode(),
                    'country' => 'US',
                ],
                emergencyContact: null,
                bio: $faker->boolean(50) ? $faker->sentence(10) : null,
            ));
            $ids[] = $employee->id;
        }

        return $ids;
    }

    private function seedInventory(Authenticatable $owner): void
    {
        $categoryService = app(CategoryService::class);
        $productService = app(ProductService::class);
        $stockService = app(InventoryItemService::class);

        $categoryNames = [
            'Electronics', 'Office Supplies', 'Furniture', 'Software Licenses',
            'Networking Equipment', 'Peripherals', 'Mobile Devices', 'Audio & Video',
            'Storage & Backup', 'Security Equipment', 'Printers & Scanners',
            'Cables & Adapters', 'Power & Charging', 'Ergonomic Accessories',
            'Facilities Equipment',
        ];

        $categoryIds = [];
        foreach ($categoryNames as $name) {
            $category = $categoryService->create($owner, new CreateCategoryData(
                name: $name,
                parentCategoryId: null,
            ));
            $categoryIds[] = $category->id;
        }

        $products = [
            ['Wireless Mouse', '24.99', '11.50'],
            ['Mechanical Keyboard', '89.99', '45.00'],
            ['27" Monitor', '249.99', '160.00'],
            ['USB-C Hub', '39.99', '18.00'],
            ['Laptop Stand', '34.99', '15.00'],
            ['Office Chair', '199.99', '110.00'],
            ['Standing Desk', '399.99', '230.00'],
            ['A4 Paper Ream (500 sheets)', '5.99', '3.20'],
            ['Whiteboard Markers (Pack of 12)', '9.99', '4.50'],
            ['Network Switch 24-port', '129.99', '78.00'],
            ['Wireless Router', '79.99', '42.00'],
            ['External SSD 1TB', '109.99', '65.00'],
            ['Webcam HD 1080p', '49.99', '24.00'],
            ['Noise-Cancelling Headphones', '159.99', '95.00'],
            ['Label Printer', '69.99', '38.00'],
        ];

        foreach ($products as $i => [$name, $unitPrice, $costPrice]) {
            $product = $productService->create($owner, new CreateProductData(
                sku: 'SKU-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                name: $name,
                description: "{$name} — demo catalogue item.",
                categoryId: $categoryIds[$i],
                unitPrice: $unitPrice,
                costPrice: $costPrice,
                isActive: true,
            ));

            // Vary stock levels: most well-stocked, a few intentionally low.
            $quantity = match (true) {
                $i % 5 === 0 => 4,   // below the default reorder point — shows as low stock
                $i % 3 === 0 => 12,
                default => 75,
            };

            $stockService->adjust($owner, $product->id, new AdjustStockData(
                quantity: $quantity,
                movementType: 'inbound',
                reason: 'Initial demo stock.',
            ));
        }
    }

    private function seedSuppliers(Authenticatable $owner): void
    {
        $service = app(SupplierService::class);
        $faker = fake();

        $suppliers = [
            ['Acme Supplies Ltd.', 'active'],
            ['TechSource Distribution', 'active'],
            ['Global Office Partners', 'active'],
            ['Northwind Electronics', 'active'],
            ['Apex Hardware Co.', 'active'],
            ['BrightPoint Peripherals', 'active'],
            ['Summit Networking Inc.', 'active'],
            ['Clearline Cables', 'active'],
            ['Vertex Furniture Group', 'active'],
            ['Prime Storage Solutions', 'active'],
            ['Horizon AV Supply', 'active'],
            ['Keystone Print Systems', 'inactive'],
            ['Metro Office Depot', 'active'],
            ['Falcon Tech Traders', 'inactive'],
            ['Union Business Supplies', 'active'],
        ];

        foreach ($suppliers as [$name, $status]) {
            $service->create($owner, new CreateSupplierData(
                name: $name,
                contactEmail: $faker->companyEmail(),
                contactPhone: $faker->phoneNumber(),
                address: [
                    'line1' => $faker->streetAddress(),
                    'city' => $faker->city(),
                    'state' => $faker->stateAbbr(),
                    'postal_code' => $faker->postcode(),
                    'country' => 'US',
                ],
                status: $status,
            ));
        }
    }

    private function seedWorkflows(Authenticatable $owner): void
    {
        $service = app(WorkflowService::class);

        $events = [
            'ticket.created', 'ticket.assigned', 'ticket.status_changed',
            'employee.created', 'employee.updated', 'employee.archived',
        ];

        $workflows = [
            ['Notify ops on ticket creation', 'ticket.created', 'send_notification'],
            ['Log every new ticket', 'ticket.created', 'log_audit_event'],
            ['Alert on ticket assignment', 'ticket.assigned', 'send_notification'],
            ['Audit ticket assignments', 'ticket.assigned', 'log_audit_event'],
            ['Notify on status change', 'ticket.status_changed', 'send_notification'],
            ['Audit status changes', 'ticket.status_changed', 'log_audit_event'],
            ['Welcome new employee', 'employee.created', 'send_notification'],
            ['Log employee onboarding', 'employee.created', 'log_audit_event'],
            ['Notify HR on profile update', 'employee.updated', 'send_notification'],
            ['Audit employee updates', 'employee.updated', 'log_audit_event'],
            ['Alert on employee archive', 'employee.archived', 'send_notification'],
            ['Audit employee archival', 'employee.archived', 'log_audit_event'],
            ['Escalation log for tickets', 'ticket.created', 'log_audit_event'],
            ['Second assignment watcher', 'ticket.assigned', 'log_audit_event'],
            ['Draft: quarterly review reminder', 'employee.updated', 'send_notification'],
        ];

        foreach ($workflows as $i => [$name, $event, $action]) {
            $steps = [
                ['type' => 'trigger', 'config' => ['kind' => 'event', 'event' => $event]],
            ];

            $steps[] = $action === 'send_notification'
                ? ['type' => 'action', 'config' => [
                    'action' => 'send_notification',
                    'to' => 'ops@example.com',
                    'subject' => $name,
                    'message' => "Triggered by {$event}.",
                ]]
                : ['type' => 'action', 'config' => [
                    'action' => 'log_audit_event',
                    'audit_action' => 'demo.'.str_replace('.', '_', $event),
                    'subject_type' => str_starts_with($event, 'ticket') ? 'ticket' : 'employee',
                    'subject_id' => str_starts_with($event, 'ticket') ? '{{ticket.id}}' : '{{employee.id}}',
                ]];

            $workflow = $service->create($owner, $name, "Demo workflow #{$i} for {$event}.", $steps);

            // Leave the last one as a draft, activate the rest, pause one.
            if ($i === count($workflows) - 1) {
                continue;
            }
            $service->activate($owner, $workflow->id);
            if ($i === 13) {
                $service->pause($owner, $workflow->id);
            }
        }
    }

    /**
     * @param  list<string>  $employeeIds
     */
    private function seedTickets(Authenticatable $owner, array $employeeIds): void
    {
        $ticketService = app(TicketService::class);
        $commentService = app(TicketCommentService::class);

        $tickets = [
            ['Laptop won\'t power on', 'hardware', 'high'],
            ['Monitor flickering intermittently', 'hardware', 'medium'],
            ['Excel crashes on file open', 'software', 'medium'],
            ['VPN client fails to connect', 'software', 'high'],
            ['Wi-Fi drops every few minutes', 'network', 'medium'],
            ['Cannot access shared drive', 'network', 'high'],
            ['Locked out of email account', 'account_access', 'critical'],
            ['Need access to Finance shared folder', 'account_access', 'low'],
            ['Printer jam on 3rd floor', 'printer', 'low'],
            ['Toner low warning won\'t clear', 'printer', 'low'],
            ['Emails not syncing on phone', 'email', 'medium'],
            ['Spam filter blocking client emails', 'email', 'high'],
            ['Suspicious phishing email received', 'security', 'critical'],
            ['Laptop flagged for malware', 'security', 'critical'],
            ['Request new monitor for desk', 'other', 'low'],
            ['Office chair needs replacement', 'other', 'low'],
            ['Keyboard keys sticking', 'hardware', 'medium'],
            ['Onboarding laptop setup request', 'other', 'medium'],
        ];

        $technicianId = $employeeIds[0];
        $ticketIds = [];

        foreach ($tickets as $i => [$subject, $type, $priority]) {
            $ticket = $ticketService->create($owner, new CreateTicketData(
                employeeId: $employeeIds[($i + 1) % count($employeeIds)],
                type: $type,
                priority: $priority,
                subject: $subject,
                description: "{$subject}. Reported during demo data seeding.",
            ));
            $ticketIds[] = $ticket->id;

            // Vary the status distribution so the list/dashboard look real.
            if ($i >= 5 && $i <= 15) {
                $ticketService->assign($owner, $ticket->id, $technicianId);
            }
            if ($i >= 9 && $i <= 15) {
                $ticketService->updateStatus($owner, $ticket->id, 'in_progress');
            }
            if ($i === 12) {
                $ticketService->updateStatus($owner, $ticket->id, 'resolved', 'Fix applied, awaiting confirmation.');
            }
            if ($i === 13 || $i === 14) {
                $ticketService->close($owner, $ticket->id, 'Resolved and verified with the requester.');
            }
            if ($i === 14) {
                $ticketService->reopen($owner, $ticket->id, 'Issue recurred.');
            }

            if ($i % 3 === 0) {
                $commentService->create($owner, $ticket->id, new CreateTicketCommentData(
                    body: 'Looking into this now.',
                    isInternal: false,
                ));
            }
            if ($i % 4 === 0) {
                $commentService->create($owner, $ticket->id, new CreateTicketCommentData(
                    body: 'Internal note: checked asset tag, ordering replacement part.',
                    isInternal: true,
                ));
            }
        }
    }

    private function drainQueue(): void
    {
        Artisan::call('queue:work', [
            '--queue' => 'automation,notifications,knowledge_base,default',
            '--stop-when-empty' => true,
            '--tries' => 1,
        ]);
    }

    private function seedAiConversations(string $tenantId, Authenticatable $owner): void
    {
        $faker = fake();

        $topics = [
            ['Question about vacation policy', 'How many PTO days do I have left this year?', 'Based on your hire date, you have accrued 14 PTO days, with 6 used so far this year.'],
            ['Help drafting a client email', 'Can you help me write a polite follow-up email?', 'Sure — here is a concise, polite follow-up you can send.'],
            ['Troubleshooting VPN access', 'My VPN keeps disconnecting, any ideas?', 'This is often caused by an unstable network route — try switching to the backup VPN gateway.'],
            ['Summarizing meeting notes', 'Can you summarize these meeting notes for me?', 'Here is a three-bullet summary of the key decisions and action items.'],
            ['Onboarding checklist question', 'What do I need to do in my first week?', 'Your first week checklist includes IT setup, benefits enrollment, and a 1:1 with your manager.'],
            ['Expense report help', 'How do I submit a travel expense report?', 'Travel expenses are submitted through the Finance portal with receipts attached.'],
            ['Drafting a job posting', 'Help me write a job posting for a QA Engineer.', 'Here is a draft job posting emphasizing test automation and cross-team collaboration.'],
            ['Explaining a ticket priority', 'What does "critical" priority mean for tickets?', 'Critical priority means a business-impacting outage requiring immediate attention.'],
            ['Product pricing question', 'What is our standard markup on hardware?', 'Standard markup on hardware products is typically 40-60% over cost price.'],
            ['Interview question suggestions', 'Suggest some interview questions for a Product Manager role.', 'Here are five behavioral and five technical interview questions for a PM role.'],
            ['Writing a status update', 'Help me write a weekly status update for my team.', 'Here is a structured weekly status update template with wins, blockers, and next steps.'],
            ['Security policy question', 'What is our policy on phishing emails?', 'Report suspicious emails to security@example.com and do not click any links.'],
            ['Vendor comparison help', 'Compare two networking equipment vendors for me.', 'Here is a side-by-side comparison based on price, support SLA, and warranty.'],
            ['Performance review prep', 'Help me prepare talking points for my performance review.', 'Here are talking points structured around impact, growth, and goals for next quarter.'],
            ['General product question', 'What products do we have in the Electronics category?', 'The Electronics category currently includes items like the Wireless Mouse and 27" Monitor.'],
        ];

        foreach ($topics as [$title, $userMsg, $assistantMsg]) {
            $conversation = AiConversation::create([
                'tenant_id' => $tenantId,
                'user_id' => $owner->getAuthIdentifier(),
                'title' => $title,
                'system_prompt' => null,
                'provider' => 'openai',
                'model' => 'gpt-4o-mini',
                'total_prompt_tokens' => $faker->numberBetween(50, 400),
                'total_completion_tokens' => $faker->numberBetween(30, 300),
            ]);

            AiMessage::create([
                'tenant_id' => $tenantId,
                'conversation_id' => $conversation->id,
                'role' => 'user',
                'content' => $userMsg,
                'tool_calls' => null,
                'tool_call_id' => null,
                'name' => null,
                'prompt_tokens' => null,
                'completion_tokens' => null,
            ]);

            AiMessage::create([
                'tenant_id' => $tenantId,
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $assistantMsg,
                'tool_calls' => null,
                'tool_call_id' => null,
                'name' => null,
                'prompt_tokens' => $faker->numberBetween(50, 400),
                'completion_tokens' => $faker->numberBetween(30, 300),
            ]);
        }
    }

    private function seedKnowledgeBase(string $tenantId, Authenticatable $owner): void
    {
        $faker = fake();

        $documents = [
            ['Employee Handbook', 'employee-handbook.pdf'],
            ['PTO and Leave Policy', 'pto-leave-policy.pdf'],
            ['IT Security Guidelines', 'it-security-guidelines.pdf'],
            ['Expense Reimbursement Policy', 'expense-policy.pdf'],
            ['Remote Work Guidelines', 'remote-work-guidelines.pdf'],
            ['Onboarding Checklist', 'onboarding-checklist.pdf'],
            ['Code of Conduct', 'code-of-conduct.pdf'],
            ['Performance Review Guide', 'performance-review-guide.pdf'],
            ['Vendor Management Policy', 'vendor-management-policy.pdf'],
            ['Data Retention Policy', 'data-retention-policy.pdf'],
            ['Incident Response Runbook', 'incident-response-runbook.pdf'],
            ['Benefits Enrollment Guide', 'benefits-enrollment-guide.pdf'],
            ['Travel Policy', 'travel-policy.pdf'],
            ['Equipment Request Process', 'equipment-request-process.pdf'],
            ['Anti-Harassment Policy', 'anti-harassment-policy.pdf'],
        ];

        foreach ($documents as $i => [$title, $filename]) {
            $isFailed = $i === 14;

            $document = Document::create([
                'tenant_id' => $tenantId,
                'uploaded_by_user_id' => $owner->getAuthIdentifier(),
                'title' => $title,
                'original_filename' => $filename,
                'file_path' => "demo/{$filename}",
                'mime_type' => 'application/pdf',
                'size_bytes' => $faker->numberBetween(50_000, 800_000),
                'status' => $isFailed ? 'failed' : 'ready',
                'error_message' => $isFailed ? 'No extractable text was found in this document.' : null,
                'page_count' => $isFailed ? null : $faker->numberBetween(2, 12),
            ]);

            if ($isFailed) {
                continue;
            }

            for ($chunkIndex = 0; $chunkIndex < 3; $chunkIndex++) {
                DocumentChunk::create([
                    'tenant_id' => $tenantId,
                    'document_id' => $document->id,
                    'chunk_index' => $chunkIndex,
                    'page_number' => $chunkIndex + 1,
                    'content' => "{$title} — section {$chunkIndex}: {$faker->paragraph(4)}",
                    'embedding' => array_fill(0, 32, 0.0),
                ]);
            }
        }
    }
}
