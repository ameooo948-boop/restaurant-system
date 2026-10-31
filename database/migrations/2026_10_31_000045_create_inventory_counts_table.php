<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
 public function up(): void
 {
  Schema::create('inventory_counts', function (Blueprint $t): void { $t->id(); $t->uuid('public_id')->unique(); $t->foreignId('branch_id')->constrained('branches')->restrictOnDelete(); $t->string('status',20)->default('draft'); $t->foreignId('counted_by')->nullable()->constrained('users')->nullOnDelete(); $t->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestampTz('counted_at'); $t->timestampTz('posted_at')->nullable(); $t->text('notes')->nullable(); $t->timestampsTz(); $t->index(['branch_id','status','counted_at'],'inventory_counts_branch_status_idx'); }); if (DB::connection()->getDriverName()==='pgsql') DB::statement("ALTER TABLE inventory_counts ADD CONSTRAINT inventory_counts_status_ck CHECK (status IN ('draft','posted','cancelled'))");
 }
 public function down(): void { Schema::dropIfExists('inventory_counts'); }
};
