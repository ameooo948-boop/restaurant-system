<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cart_id')->constrained('carts')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->restrictOnDelete();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->string('notes', 500)->nullable();
            $table->timestampsTz();

            $table->index(['cart_id', 'product_variant_id'], 'cart_items_cart_variant_index');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE cart_items ADD CONSTRAINT cart_items_quantity_check CHECK (quantity BETWEEN 1 AND 20)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
