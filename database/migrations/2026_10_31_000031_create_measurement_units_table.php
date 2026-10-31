<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
 public function up(): void
 {
  Schema::create('measurement_units', function (Blueprint $t): void { $t->id(); $t->foreignId('restaurant_id')->constrained('restaurants')->restrictOnDelete(); $t->string('name',80); $t->string('symbol',20); $t->string('dimension',20); $t->boolean('is_active')->default(true); $t->timestampsTz(); $t->unique(['restaurant_id','symbol'],'measurement_units_restaurant_symbol_uq'); $t->index(['restaurant_id','dimension','is_active'],'measurement_units_restaurant_dimension_idx'); });
 }
 public function down(): void { Schema::dropIfExists('measurement_units'); }
};
