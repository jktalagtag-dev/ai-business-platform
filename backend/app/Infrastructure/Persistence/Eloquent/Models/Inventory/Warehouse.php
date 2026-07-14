<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models\Inventory;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasUlids;

    /**
     * @var list<string>
     */
    protected $fillable = ['tenant_id', 'name'];
}
