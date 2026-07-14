<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models\Employee;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeNote extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = ['tenant_id', 'employee_id', 'author_user_id', 'note'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
