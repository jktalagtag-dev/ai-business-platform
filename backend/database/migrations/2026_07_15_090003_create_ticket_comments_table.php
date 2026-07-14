<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_comments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('author_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->text('body');
            // Internal notes are visible to technicians/managers/admins only —
            // hidden from the requesting employee.
            $table->boolean('is_internal')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'ticket_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_comments');
    }
};
