<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kb_documents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('uploaded_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('title');
            $table->string('original_filename');
            // Self-contained (path/mime/size stored directly) rather than
            // depending on a generic `files` table — that table was sketched
            // in DATABASE.md's original design but was never built by any
            // shipped module (Employee's avatar upload and Ticketing's
            // attachments both took the same self-contained approach).
            $table->string('file_path');
            $table->string('mime_type');
            $table->unsignedBigInteger('size_bytes');
            $table->string('status')->default('processing');
            $table->text('error_message')->nullable();
            $table->unsignedInteger('page_count')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kb_documents');
    }
};
