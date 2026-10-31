<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
 public function up(): void
 {
  Schema::create('recipe_items', function (Blueprint $t): void { $t->id(); $t->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete(); $t->foreignId('ingredient_id')->constrained('ingredients')->restrictOnDelete(); $t->decimal('quantity',14,3); $t->decimal('waste_percent',5,2)->default(0); $t->timestampsTz(); $t->unique(['recipe_id','ingredient_id'],'recipe_items_recipe_ingredient_uq'); }); if (DB::connection()->getDriverName()==='pgsql') { DB::statement('ALTER TABLE recipe_items ADD CONSTRAINT recipe_items_quantity_positive_ck CHECK (quantity > 0)'); DB::statement('ALTER TABLE recipe_items ADD CONSTRAINT recipe_items_waste_range_ck CHECK (waste_percent >= 0 AND waste_percent <= 100)'); }
 }
 public function down(): void { Schema::dropIfExists('recipe_items'); }
};
