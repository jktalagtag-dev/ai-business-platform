<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            // Falls back to config('ai.default_system_prompt') when null.
            $table->text('system_prompt')->nullable();
            $table->string('provider')->default('openai');
            $table->string('model');
            $table->unsignedInteger('total_prompt_tokens')->default(0);
            $table->unsignedInteger('total_completion_tokens')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_conversations');
    }
};
