<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
 public function up(): void
 {
  Schema::create('recipes', function (Blueprint $t): void { $t->id(); $t->foreignId('product_variant_id')->unique()->constrained('product_variants')->cascadeOnDelete(); $t->decimal('yield_quantity',14,3)->default(1); $t->text('instructions')->nullable(); $t->boolean('is_active')->default(true); $t->timestampsTz(); }); if (DB::connection()->getDriverName()==='pgsql') DB::statement('ALTER TABLE recipes ADD CONSTRAINT recipes_yield_positive_ck CHECK (yield_quantity > 0)');
 }
 public function down(): void { Schema::dropIfExists('recipes'); }
};
