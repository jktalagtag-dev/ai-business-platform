<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('employee_number');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->foreignUlid('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignUlid('position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->foreignUlid('manager_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('employment_type')->default('full_time');
            $table->string('employment_status')->default('active');
            $table->date('hire_date');
            $table->date('termination_date')->nullable();
            $table->jsonb('address')->nullable();
            $table->jsonb('emergency_contact')->nullable();
            $table->string('avatar_path')->nullable();
            $table->text('bio')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'employee_number']);
            $table->unique(['tenant_id', 'email']);
            $table->index(['tenant_id', 'department_id']);
            $table->index(['tenant_id', 'manager_employee_id']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
