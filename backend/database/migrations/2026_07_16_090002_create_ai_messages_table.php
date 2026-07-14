<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_messages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('conversation_id')->constrained('ai_conversations')->cascadeOnDelete();
            $table->string('role');
            // Nullable: an assistant message that is pure tool_calls (no
            // user-visible text) has no content.
            $table->text('content')->nullable();
            // Assistant-only: the function calls the model requested, e.g.
            // [{"id": "...", "name": "...", "arguments": "{...}"}].
            $table->jsonb('tool_calls')->nullable();
            // Tool-role messages only: which call this result answers, and
            // the function name, mirroring the OpenAI message shape.
            $table->string('tool_call_id')->nullable();
            $table->string('name')->nullable();
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->timestamp('created_at');

            $table->index(['tenant_id', 'conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_messages');
    }
};
