<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->restrictOnDelete();
            $table->string('ticket_number');
            $table->foreignUlid('employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignUlid('assigned_technician_id')->nullable()->constrained('employees')->nullOnDelete();
            // Snapshot of the requester's department at creation time — kept
            // stable for reporting/manager-scoping even if the employee later
            // transfers departments.
            $table->foreignUlid('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('type');
            $table->string('priority')->default('medium');
            $table->string('status')->default('open');
            $table->string('subject');
            $table->text('description');
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            // Set the first time SlaMonitoringJob detects this ticket breaching
            // its resolution-time target, so escalation is a one-time event per
            // breach rather than repeating on every scheduler tick. Cleared if
            // the ticket is reopened after resolution.
            $table->timestamp('sla_breached_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'ticket_number']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'priority']);
            $table->index(['tenant_id', 'employee_id']);
            $table->index(['tenant_id', 'assigned_technician_id']);
            $table->index(['tenant_id', 'department_id']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
