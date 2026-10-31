<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
 public function up(): void
 {
  Schema::create('ingredient_unit_conversions', function (Blueprint $t): void { $t->id(); $t->foreignId('ingredient_id')->constrained('ingredients')->cascadeOnDelete(); $t->foreignId('unit_id')->constrained('measurement_units')->restrictOnDelete(); $t->decimal('to_base_factor',18,6); $t->timestampsTz(); $t->unique(['ingredient_id','unit_id'],'ingredient_unit_conversion_uq'); }); if (DB::connection()->getDriverName()==='pgsql') DB::statement('ALTER TABLE ingredient_unit_conversions ADD CONSTRAINT ingredient_unit_factor_positive_ck CHECK (to_base_factor > 0)');
 }
 public function down(): void { Schema::dropIfExists('ingredient_unit_conversions'); }
};
