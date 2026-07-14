<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per tenant, incremented atomically (SELECT ... FOR UPDATE)
     * when a new ticket is created, mirroring employee_id_sequences —
     * generates collision-free ticket numbers (e.g. TCK-000123).
     */
    public function up(): void
    {
        Schema::create('ticket_id_sequences', function (Blueprint $table) {
            $table->foreignUlid('tenant_id')->primary()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('next_number')->default(1);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_id_sequences');
    }
};
