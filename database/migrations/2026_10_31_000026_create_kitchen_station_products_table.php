<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kitchen_station_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('kitchen_station_id')->constrained('kitchen_stations')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->timestampsTz();

            $table->unique(['branch_id', 'product_id'], 'kitchen_station_products_branch_product_unique');
            $table->index(['branch_id', 'kitchen_station_id'], 'kitchen_station_products_branch_station_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kitchen_station_products');
    }
};
