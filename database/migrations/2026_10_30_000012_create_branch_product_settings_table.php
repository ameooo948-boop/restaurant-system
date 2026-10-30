<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_product_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->decimal('price_override', 12, 2)->nullable();
            $table->boolean('is_available')->default(true);
            $table->time('available_from')->nullable();
            $table->time('available_until')->nullable();
            $table->timestampsTz();

            $table->unique(['branch_id', 'product_variant_id'], 'branch_product_settings_branch_variant_unique');
            $table->index(['branch_id', 'is_available'], 'branch_product_settings_branch_available_index');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE branch_product_settings ADD CONSTRAINT branch_product_settings_price_nonnegative CHECK (price_override IS NULL OR price_override >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_product_settings');
    }
};
