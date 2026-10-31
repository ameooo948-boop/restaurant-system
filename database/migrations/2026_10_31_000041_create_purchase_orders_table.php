<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
 public function up(): void
 {
  Schema::create('purchase_orders', function (Blueprint $t): void { $t->id(); $t->uuid('public_id')->unique(); $t->foreignId('branch_id')->constrained('branches')->restrictOnDelete(); $t->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete(); $t->string('purchase_number',80); $t->string('status',24)->default('ordered'); $t->char('currency',3)->default('EGP'); $t->decimal('total_amount',14,2)->default(0); $t->text('notes')->nullable(); $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestampTz('ordered_at'); $t->timestampTz('received_at')->nullable(); $t->timestampsTz(); $t->unique(['branch_id','purchase_number'],'purchase_orders_branch_number_uq'); $t->index(['branch_id','status','ordered_at'],'purchase_orders_branch_status_idx'); }); if (DB::connection()->getDriverName()==='pgsql') { DB::statement("ALTER TABLE purchase_orders ADD CONSTRAINT purchase_orders_status_ck CHECK (status IN ('draft','ordered','partially_received','received','cancelled'))"); DB::statement('ALTER TABLE purchase_orders ADD CONSTRAINT purchase_orders_total_nonnegative_ck CHECK (total_amount >= 0)'); }
 }
 public function down(): void { Schema::dropIfExists('purchase_orders'); }
};
