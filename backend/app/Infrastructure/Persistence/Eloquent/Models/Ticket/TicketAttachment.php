<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models\Ticket;

use App\Infrastructure\Persistence\Eloquent\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketAttachment extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'ticket_id',
        'uploaded_by_employee_id',
        'file_path',
        'original_filename',
        'mime_type',
        'size_bytes',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'uploaded_by_employee_id');
    }
}
