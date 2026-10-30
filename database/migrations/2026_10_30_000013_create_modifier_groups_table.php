<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modifier_groups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->restrictOnDelete();
            $table->string('name', 120);
            $table->json('translations')->nullable();
            $table->text('description')->nullable();
            $table->string('selection_type', 20)->default('single');
            $table->unsignedSmallInteger('min_selections')->default(0);
            $table->unsignedSmallInteger('max_selections')->nullable();
            $table->smallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['restaurant_id', 'is_active', 'sort_order'], 'modifier_groups_restaurant_active_sort_index');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE modifier_groups ADD CONSTRAINT modifier_groups_selection_type_check CHECK (selection_type IN ('single', 'multiple'))");
            DB::statement('ALTER TABLE modifier_groups ADD CONSTRAINT modifier_groups_selection_bounds_check CHECK (max_selections IS NULL OR max_selections >= min_selections)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('modifier_groups');
    }
};
