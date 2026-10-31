<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_item_modifiers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('modifier_option_id')->nullable()->constrained('modifier_options')->nullOnDelete();
            $table->string('group_name_snapshot', 120);
            $table->string('option_name_snapshot', 120);
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->decimal('unit_price_delta', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->timestampsTz();
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE order_item_modifiers ADD CONSTRAINT order_item_modifiers_quantity_check CHECK (quantity > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_modifiers');
    }
};
