<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->restrictOnDelete();
            // The self-referencing FK is added below, after this table's
            // primary key exists — Laravel queues a column's ->primary()
            // constraint after any ->constrained() foreign keys declared in
            // the same Schema::create, so a self-referencing FK declared
            // inline here would be built before its own PK exists.
            $table->foreignUlid('parent_department_id')->nullable();
            // manager_employee_id is added in a follow-up migration once the
            // employees table exists — departments and employees reference
            // each other, so one side must be created first without it.
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'parent_department_id', 'name']);
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->foreign('parent_department_id')->references('id')->on('departments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
