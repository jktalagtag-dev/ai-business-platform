<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per ordered step: exactly one `trigger` step at step_order 0,
     * zero-or-more `condition` steps, one-or-more `action` steps — validated
     * by StoreWorkflowRequest, not the database. `config` shape depends on
     * `type`:
     *   trigger:   {kind: event|schedule, event?: string, cron?: string}
     *   condition: {field: string, operator: string, value: mixed}
     *   action:    {action: string, ...action-specific params}
     */
    public function up(): void
    {
        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('workflow_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('step_order');
            $table->string('type');
            $table->jsonb('config');
            $table->timestamps();

            $table->unique(['workflow_id', 'step_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_steps');
    }
};
