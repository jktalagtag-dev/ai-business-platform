<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-step audit trail of one automation_jobs run. `type`/`step_order`
     * are denormalized copies from workflow_steps (rather than requiring a
     * join, or breaking if a step is later deleted) so a run's history
     * always renders fully on its own.
     */
    public function up(): void
    {
        Schema::create('automation_job_steps', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('automation_job_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('workflow_step_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('step_order');
            $table->string('type');
            $table->string('status')->default('pending');
            $table->jsonb('output')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('created_at');

            $table->index(['tenant_id', 'automation_job_id', 'step_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_job_steps');
    }
};
