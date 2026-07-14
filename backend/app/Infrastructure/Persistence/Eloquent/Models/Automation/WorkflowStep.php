<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models\Automation;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowStep extends Model
{
    use HasUlids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'workflow_id',
        'step_order',
        'type',
        'config',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'config' => 'array',
        ];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }
}
