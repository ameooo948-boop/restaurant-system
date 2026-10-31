<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
 public function up(): void
 {
  Schema::create('stock_movements', function (Blueprint $t): void { $t->id(); $t->foreignId('branch_id')->constrained('branches')->restrictOnDelete(); $t->foreignId('ingredient_id')->constrained('ingredients')->restrictOnDelete(); $t->string('movement_type',30); $t->decimal('quantity_delta',14,3); $t->decimal('unit_cost',14,4)->nullable(); $t->string('source_type',60)->nullable(); $t->unsignedBigInteger('source_id')->nullable(); $t->string('reference',120)->nullable(); $t->text('note')->nullable(); $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestampTz('occurred_at'); $t->timestampsTz(); $t->index(['branch_id','ingredient_id','occurred_at'],'stock_movements_branch_ingredient_date_idx'); $t->index(['source_type','source_id'],'stock_movements_source_idx'); }); if (DB::connection()->getDriverName()==='pgsql') { DB::statement("ALTER TABLE stock_movements ADD CONSTRAINT stock_movement_type_ck CHECK (movement_type IN ('opening','adjustment','purchase_receipt','consumption','count_adjustment','reversal'))"); DB::statement('ALTER TABLE stock_movements ADD CONSTRAINT stock_movement_delta_nonzero_ck CHECK (quantity_delta <> 0)'); }
 }
 public function down(): void { Schema::dropIfExists('stock_movements'); }
};
