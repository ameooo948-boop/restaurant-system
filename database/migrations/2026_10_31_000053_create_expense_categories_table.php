<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->restrictOnDelete();
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->unique(['restaurant_id', 'name'], 'expense_categories_restaurant_name_uq');
            $table->index(['restaurant_id', 'is_active'], 'expense_categories_restaurant_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_categories');
    }
};
