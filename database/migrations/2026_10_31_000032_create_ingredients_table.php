<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
 public function up(): void
 {
  Schema::create('ingredients', function (Blueprint $t): void { $t->id(); $t->foreignId('restaurant_id')->constrained('restaurants')->restrictOnDelete(); $t->foreignId('base_unit_id')->constrained('measurement_units')->restrictOnDelete(); $t->string('name',150); $t->string('sku',80)->nullable(); $t->text('description')->nullable(); $t->decimal('reorder_level',14,3)->default(0); $t->boolean('is_active')->default(true); $t->timestampsTz(); $t->softDeletesTz(); $t->unique(['restaurant_id','sku'],'ingredients_restaurant_sku_uq'); $t->index(['restaurant_id','is_active','name'],'ingredients_restaurant_active_name_idx'); }); if (DB::connection()->getDriverName()==='pgsql') DB::statement('ALTER TABLE ingredients ADD CONSTRAINT ingredients_reorder_nonnegative_ck CHECK (reorder_level >= 0)');
 }
 public function down(): void { Schema::dropIfExists('ingredients'); }
};
