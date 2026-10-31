<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
 public function up(): void
 {
  Schema::create('suppliers', function (Blueprint $t): void { $t->id(); $t->foreignId('restaurant_id')->constrained('restaurants')->restrictOnDelete(); $t->string('name',150); $t->string('contact_name',120)->nullable(); $t->string('phone',30)->nullable(); $t->string('email')->nullable(); $t->text('address')->nullable(); $t->text('notes')->nullable(); $t->boolean('is_active')->default(true); $t->timestampsTz(); $t->index(['restaurant_id','is_active','name'],'suppliers_restaurant_active_name_idx'); });
 }
 public function down(): void { Schema::dropIfExists('suppliers'); }
};
