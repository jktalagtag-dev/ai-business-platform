<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models\KnowledgeBase;

use App\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    use HasUlids;

    protected $table = 'kb_documents';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'uploaded_by_user_id',
        'title',
        'original_filename',
        'file_path',
        'mime_type',
        'size_bytes',
        'status',
        'error_message',
        'page_count',
    ];

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(DocumentChunk::class, 'document_id');
    }
}
