<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
 public function up(): void
 {
  Schema::create('stock_reservations', function (Blueprint $t): void { $t->id(); $t->foreignId('branch_id')->constrained('branches')->restrictOnDelete(); $t->foreignId('order_id')->constrained('orders')->restrictOnDelete(); $t->foreignId('kitchen_ticket_id')->constrained('kitchen_tickets')->restrictOnDelete(); $t->foreignId('kitchen_ticket_item_id')->constrained('kitchen_ticket_items')->restrictOnDelete(); $t->foreignId('ingredient_id')->constrained('ingredients')->restrictOnDelete(); $t->decimal('reserved_qty',14,3); $t->decimal('consumed_qty',14,3)->default(0); $t->string('status',20)->default('reserved'); $t->timestampTz('consumed_at')->nullable(); $t->timestampTz('released_at')->nullable(); $t->timestampsTz(); $t->unique(['kitchen_ticket_item_id','ingredient_id'],'stock_reservation_ticket_item_ingredient_uq'); $t->index(['order_id','status'],'stock_reservations_order_status_idx'); $t->index(['kitchen_ticket_id','status'],'stock_reservations_ticket_status_idx'); }); if (DB::connection()->getDriverName()==='pgsql') { DB::statement("ALTER TABLE stock_reservations ADD CONSTRAINT stock_reservation_status_ck CHECK (status IN ('reserved','consumed','released'))"); DB::statement('ALTER TABLE stock_reservations ADD CONSTRAINT stock_reservation_quantities_ck CHECK (reserved_qty > 0 AND consumed_qty >= 0 AND consumed_qty <= reserved_qty)'); }
 }
 public function down(): void { Schema::dropIfExists('stock_reservations'); }
};
