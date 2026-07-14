<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `embedding` is stored as a jsonb array of floats rather than a native
     * pgvector `vector` column — this project's tests run against SQLite
     * (see phpunit.xml), which pgvector can't run on, and every other
     * module has taken the same "stay SQL-portable" trade-off (e.g.
     * TicketRepository::statistics() computing averages in PHP rather than
     * Postgres date-arithmetic). Similarity search is brute-force cosine
     * similarity computed in PHP (see Domain\KnowledgeBase\VectorMath) —
     * fine at typical internal-KB volumes; a real vector column + ANN
     * index is a natural follow-up if a tenant's corpus outgrows it.
     */
    public function up(): void
    {
        Schema::create('kb_document_chunks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('document_id')->constrained('kb_documents')->cascadeOnDelete();
            $table->unsignedInteger('chunk_index');
            // Nullable: chunking doesn't always cleanly attribute to one
            // page (rare edge cases in extraction), so this is best-effort
            // for citation display rather than a hard guarantee.
            $table->unsignedInteger('page_number')->nullable();
            $table->text('content');
            $table->jsonb('embedding');
            $table->timestamp('created_at');

            $table->unique(['document_id', 'chunk_index']);
            $table->index(['tenant_id', 'document_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kb_document_chunks');
    }
};
