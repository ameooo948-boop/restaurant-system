<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
 public function up(): void
 {
  Schema::create('purchase_order_items', function (Blueprint $t): void { $t->id(); $t->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete(); $t->foreignId('ingredient_id')->constrained('ingredients')->restrictOnDelete(); $t->decimal('quantity_ordered',14,3); $t->decimal('quantity_received',14,3)->default(0); $t->decimal('unit_cost',14,4); $t->timestampsTz(); $t->index(['purchase_order_id','ingredient_id'],'purchase_order_items_po_ingredient_idx'); }); if (DB::connection()->getDriverName()==='pgsql') DB::statement('ALTER TABLE purchase_order_items ADD CONSTRAINT purchase_order_items_quantities_ck CHECK (quantity_ordered > 0 AND quantity_received >= 0 AND quantity_received <= quantity_ordered AND unit_cost >= 0)');
 }
 public function down(): void { Schema::dropIfExists('purchase_order_items'); }
};
