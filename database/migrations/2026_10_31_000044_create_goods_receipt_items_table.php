<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
 public function up(): void
 {
  Schema::create('goods_receipt_items', function (Blueprint $t): void { $t->id(); $t->foreignId('goods_receipt_id')->constrained('goods_receipts')->cascadeOnDelete(); $t->foreignId('purchase_order_item_id')->constrained('purchase_order_items')->restrictOnDelete(); $t->foreignId('ingredient_id')->constrained('ingredients')->restrictOnDelete(); $t->decimal('received_qty',14,3); $t->decimal('unit_cost',14,4); $t->timestampsTz(); $t->unique(['goods_receipt_id','purchase_order_item_id'],'goods_receipt_po_item_uq'); }); if (DB::connection()->getDriverName()==='pgsql') DB::statement('ALTER TABLE goods_receipt_items ADD CONSTRAINT goods_receipt_items_amount_ck CHECK (received_qty > 0 AND unit_cost >= 0)');
 }
 public function down(): void { Schema::dropIfExists('goods_receipt_items'); }
};
