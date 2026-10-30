<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('menu_categories')->nullOnDelete();
            $table->string('name', 160);
            $table->json('translations')->nullable();
            $table->string('slug', 180);
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->smallInteger('sort_order')->default(0);
            $table->unsignedSmallInteger('preparation_time_minutes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique(['restaurant_id', 'slug'], 'products_restaurant_slug_unique');
            $table->index(['restaurant_id', 'is_active', 'sort_order'], 'products_restaurant_active_sort_index');
            $table->index(['restaurant_id', 'category_id'], 'products_restaurant_category_index');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE products ADD CONSTRAINT products_preparation_time_nonnegative CHECK (preparation_time_minutes IS NULL OR preparation_time_minutes >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
