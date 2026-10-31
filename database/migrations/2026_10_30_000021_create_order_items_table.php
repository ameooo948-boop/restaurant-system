<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('product_name_snapshot', 160);
            $table->string('variant_name_snapshot', 100);
            $table->string('sku_snapshot', 100)->nullable();
            $table->unsignedSmallInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('line_subtotal', 12, 2);
            $table->string('notes', 500)->nullable();
            $table->json('product_snapshot')->nullable();
            $table->timestampsTz();

            $table->index(['order_id', 'id'], 'order_items_order_index');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE order_items ADD CONSTRAINT order_items_amounts_check CHECK (quantity > 0 AND unit_price >= 0 AND line_subtotal >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
