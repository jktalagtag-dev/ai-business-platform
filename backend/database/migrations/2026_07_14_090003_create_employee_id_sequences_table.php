<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per tenant, incremented atomically (SELECT ... FOR UPDATE)
     * when a new employee is created, to generate collision-free
     * human-readable employee numbers (e.g. EMP-000123) without a gap-prone
     * COUNT(*)-based scheme.
     */
    public function up(): void
    {
        Schema::create('employee_id_sequences', function (Blueprint $table) {
            $table->foreignUlid('tenant_id')->primary()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('next_number')->default(1);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_id_sequences');
    }
};
