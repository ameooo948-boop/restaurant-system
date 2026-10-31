<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
 public function up(): void
 {
  Schema::create('modifier_option_ingredients', function (Blueprint $t): void { $t->id(); $t->foreignId('modifier_option_id')->constrained('modifier_options')->cascadeOnDelete(); $t->foreignId('ingredient_id')->constrained('ingredients')->restrictOnDelete(); $t->decimal('quantity_per_option',14,3); $t->timestampsTz(); $t->unique(['modifier_option_id','ingredient_id'],'modifier_option_ingredient_uq'); }); if (DB::connection()->getDriverName()==='pgsql') DB::statement('ALTER TABLE modifier_option_ingredients ADD CONSTRAINT modifier_option_ingredient_qty_ck CHECK (quantity_per_option > 0)');
 }
 public function down(): void { Schema::dropIfExists('modifier_option_ingredients'); }
};
