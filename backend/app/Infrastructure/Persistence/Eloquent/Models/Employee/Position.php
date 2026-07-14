<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models\Employee;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Position extends Model
{
    use HasUlids, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = ['tenant_id', 'title', 'description'];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
