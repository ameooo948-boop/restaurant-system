<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
 public function up(): void
 {
  Schema::create('inventory_count_items', function (Blueprint $t): void { $t->id(); $t->foreignId('inventory_count_id')->constrained('inventory_counts')->cascadeOnDelete(); $t->foreignId('ingredient_id')->constrained('ingredients')->restrictOnDelete(); $t->decimal('expected_qty',14,3)->default(0); $t->decimal('counted_qty',14,3)->nullable(); $t->decimal('variance_qty',14,3)->nullable(); $t->string('note',500)->nullable(); $t->timestampsTz(); $t->unique(['inventory_count_id','ingredient_id'],'inventory_count_item_uq'); }); if (DB::connection()->getDriverName()==='pgsql') { DB::statement('ALTER TABLE inventory_count_items ADD CONSTRAINT inventory_count_items_expected_nonnegative_ck CHECK (expected_qty >= 0)'); DB::statement('ALTER TABLE inventory_count_items ADD CONSTRAINT inventory_count_items_counted_nonnegative_ck CHECK (counted_qty IS NULL OR counted_qty >= 0)'); }
 }
 public function down(): void { Schema::dropIfExists('inventory_count_items'); }
};
