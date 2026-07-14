<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models\Ticket;

use App\Infrastructure\Persistence\Eloquent\Models\Employee\Department;
use App\Infrastructure\Persistence\Eloquent\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasUlids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'ticket_number',
        'employee_id',
        'assigned_technician_id',
        'department_id',
        'type',
        'priority',
        'status',
        'subject',
        'description',
        'resolution_notes',
        'resolved_at',
        'closed_at',
        'sla_breached_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'sla_breached_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function assignedTechnician(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_technician_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }
}
