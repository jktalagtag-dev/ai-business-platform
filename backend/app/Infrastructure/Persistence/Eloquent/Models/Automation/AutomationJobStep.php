<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models\Automation;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationJobStep extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'automation_job_id',
        'workflow_step_id',
        'step_order',
        'type',
        'status',
        'output',
        'error',
        'started_at',
        'finished_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'output' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function automationJob(): BelongsTo
    {
        return $this->belongsTo(AutomationJob::class);
    }
}
