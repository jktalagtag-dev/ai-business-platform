<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Self-contained (file_path/mime_type/size_bytes stored directly)
     * rather than depending on a generic `files` table — that table was
     * sketched in DATABASE.md's original design but was never built by any
     * shipped module (Employee's avatar upload took the same self-contained
     * approach), so introducing it now for this one module would be scope
     * creep, not "following the existing architecture".
     */
    public function up(): void
    {
        Schema::create('ticket_attachments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('uploaded_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->unsignedBigInteger('size_bytes');
            $table->timestamp('created_at');

            $table->index(['tenant_id', 'ticket_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_attachments');
    }
};
