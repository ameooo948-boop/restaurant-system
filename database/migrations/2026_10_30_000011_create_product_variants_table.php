<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('name', 100);
            $table->json('translations')->nullable();
            $table->string('sku', 100)->nullable()->unique();
            $table->decimal('base_price', 12, 2);
            $table->smallInteger('sort_order')->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->index(['product_id', 'is_active', 'sort_order'], 'product_variants_product_active_sort_index');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE product_variants ADD CONSTRAINT product_variants_base_price_nonnegative CHECK (base_price >= 0)');
            DB::statement('CREATE UNIQUE INDEX product_variants_single_default_unique ON product_variants (product_id) WHERE is_default = TRUE');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS product_variants_single_default_unique');
        }

        Schema::dropIfExists('product_variants');
    }
};
