<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_modifier_prices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('modifier_option_id')->constrained('modifier_options')->cascadeOnDelete();
            $table->decimal('price_delta_override', 12, 2)->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestampsTz();

            $table->unique(['branch_id', 'modifier_option_id'], 'branch_modifier_prices_branch_option_unique');
            $table->index(['branch_id', 'is_available'], 'branch_modifier_prices_branch_available_index');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE branch_modifier_prices ADD CONSTRAINT branch_modifier_prices_delta_range CHECK (price_delta_override IS NULL OR price_delta_override BETWEEN -9999999999.99 AND 9999999999.99)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_modifier_prices');
    }
};
