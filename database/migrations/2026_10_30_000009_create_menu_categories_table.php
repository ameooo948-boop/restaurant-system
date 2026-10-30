<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->restrictOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('menu_categories')->nullOnDelete();
            $table->string('name', 120);
            $table->json('translations')->nullable();
            $table->string('slug', 150);
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->smallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique(['restaurant_id', 'slug'], 'menu_categories_restaurant_slug_unique');
            $table->index(['restaurant_id', 'is_active', 'sort_order'], 'menu_categories_restaurant_active_sort_index');
            $table->index(['restaurant_id', 'parent_id'], 'menu_categories_restaurant_parent_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_categories');
    }
};
