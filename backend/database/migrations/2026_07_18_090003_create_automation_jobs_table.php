<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per execution instance of a workflow. `context` is the flat
     * event/trigger payload (e.g. {"ticket": {"id": "...", "priority":
     * "critical", ...}}) captured at trigger time — condition evaluation
     * and action placeholder substitution both read from it, and it stays
     * on the row for auditability even after the run completes.
     */
    public function up(): void
    {
        Schema::create('automation_jobs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('workflow_id')->constrained()->cascadeOnDelete();
            // The event key that fired this run (e.g. "ticket.created"), or
            // "schedule" for a cron-triggered run.
            $table->string('trigger');
            $table->string('status')->default('queued');
            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedInteger('max_attempts')->default(3);
            $table->jsonb('context')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'workflow_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_jobs');
    }
};
