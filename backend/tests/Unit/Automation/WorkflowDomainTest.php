<?php

declare(strict_types=1);

use App\Domain\Automation\Workflow;

function makeWorkflow(array $overrides = []): Workflow
{
    return new Workflow(
        id: $overrides['id'] ?? 'wf_01',
        tenantId: $overrides['tenantId'] ?? 'tenant_01',
        createdByUserId: $overrides['createdByUserId'] ?? 'user_01',
        name: $overrides['name'] ?? 'Notify on critical ticket',
        description: $overrides['description'] ?? null,
        status: $overrides['status'] ?? 'draft',
        lastTriggeredAt: $overrides['lastTriggeredAt'] ?? null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );
}

it('is active only when status is active', function () {
    expect(makeWorkflow(['status' => 'active'])->isActive())->toBeTrue();
    expect(makeWorkflow(['status' => 'draft'])->isActive())->toBeFalse();
    expect(makeWorkflow(['status' => 'paused'])->isActive())->toBeFalse();
});
