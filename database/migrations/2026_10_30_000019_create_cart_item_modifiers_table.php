<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_item_modifiers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cart_item_id')->constrained('cart_items')->cascadeOnDelete();
            $table->foreignId('modifier_option_id')->constrained('modifier_options')->restrictOnDelete();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->timestampsTz();

            $table->unique(['cart_item_id', 'modifier_option_id'], 'cart_item_modifiers_item_option_unique');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE cart_item_modifiers ADD CONSTRAINT cart_item_modifiers_quantity_check CHECK (quantity BETWEEN 1 AND 10)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_item_modifiers');
    }
};
