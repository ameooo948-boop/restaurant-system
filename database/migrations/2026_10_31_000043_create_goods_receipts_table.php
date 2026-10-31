<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
 public function up(): void
 {
  Schema::create('goods_receipts', function (Blueprint $t): void { $t->id(); $t->uuid('public_id')->unique(); $t->uuid('idempotency_key')->unique(); $t->foreignId('purchase_order_id')->constrained('purchase_orders')->restrictOnDelete(); $t->foreignId('branch_id')->constrained('branches')->restrictOnDelete(); $t->string('receipt_number',90); $t->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestampTz('received_at'); $t->text('notes')->nullable(); $t->timestampsTz(); $t->unique(['branch_id','receipt_number'],'goods_receipts_branch_number_uq'); $t->index(['purchase_order_id','received_at'],'goods_receipts_po_date_idx'); });
 }
 public function down(): void { Schema::dropIfExists('goods_receipts'); }
};
