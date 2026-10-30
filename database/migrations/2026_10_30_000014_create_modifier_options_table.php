<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modifier_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('modifier_group_id')->constrained('modifier_groups')->cascadeOnDelete();
            $table->string('name', 120);
            $table->json('translations')->nullable();
            $table->text('description')->nullable();
            $table->decimal('price_delta', 12, 2)->default(0);
            $table->smallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->unique(['modifier_group_id', 'name'], 'modifier_options_group_name_unique');
            $table->index(['modifier_group_id', 'is_active', 'sort_order'], 'modifier_options_group_active_sort_index');
        });

        // Negative price deltas are permitted for discounts/removals; the order
        // calculation layer must reject a final line price below zero.
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE modifier_options ADD CONSTRAINT modifier_options_price_delta_range CHECK (price_delta BETWEEN -9999999999.99 AND 9999999999.99)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('modifier_options');
    }
};
