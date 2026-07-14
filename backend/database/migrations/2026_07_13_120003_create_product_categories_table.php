<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->restrictOnDelete();
            // The self-referencing FK is added below, after this table's
            // primary key exists — Laravel queues a column's ->primary()
            // constraint after any ->constrained() foreign keys declared in
            // the same Schema::create, so a self-referencing FK declared
            // inline here would be built before its own PK exists.
            $table->foreignUlid('parent_category_id')->nullable();
            $table->string('name');
            $table->timestamps();

            $table->unique(['tenant_id', 'parent_category_id', 'name']);
        });

        Schema::table('product_categories', function (Blueprint $table) {
            $table->foreign('parent_category_id')->references('id')->on('product_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_categories');
    }
};
