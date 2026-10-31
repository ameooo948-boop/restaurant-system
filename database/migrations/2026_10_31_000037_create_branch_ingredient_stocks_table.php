<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
 public function up(): void
 {
  Schema::create('branch_ingredient_stocks', function (Blueprint $t): void { $t->id(); $t->foreignId('branch_id')->constrained('branches')->cascadeOnDelete(); $t->foreignId('ingredient_id')->constrained('ingredients')->restrictOnDelete(); $t->decimal('on_hand_qty',14,3)->default(0); $t->decimal('reserved_qty',14,3)->default(0); $t->decimal('reorder_level_override',14,3)->nullable(); $t->timestampsTz(); $t->unique(['branch_id','ingredient_id'],'branch_ingredient_stock_uq'); $t->index(['branch_id','on_hand_qty','reserved_qty'],'branch_ingredient_stock_balance_idx'); }); if (DB::connection()->getDriverName()==='pgsql') { DB::statement('ALTER TABLE branch_ingredient_stocks ADD CONSTRAINT branch_stock_nonnegative_ck CHECK (on_hand_qty >= 0 AND reserved_qty >= 0 AND reserved_qty <= on_hand_qty)'); DB::statement('ALTER TABLE branch_ingredient_stocks ADD CONSTRAINT branch_stock_reorder_nonnegative_ck CHECK (reorder_level_override IS NULL OR reorder_level_override >= 0)'); }
 }
 public function down(): void { Schema::dropIfExists('branch_ingredient_stocks'); }
};
