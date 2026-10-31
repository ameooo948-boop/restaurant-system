<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('delivery_zone_id')->nullable()->constrained('delivery_zones')->nullOnDelete();
            $table->foreignId('driver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('pending_assignment');
            $table->timestampTz('assigned_at')->nullable();
            $table->timestampTz('picked_up_at')->nullable();
            $table->timestampTz('delivered_at')->nullable();
            $table->timestampTz('failed_at')->nullable();
            $table->string('failure_reason', 500)->nullable();
            $table->timestampsTz();
            $table->index(['branch_id', 'status', 'created_at'], 'deliveries_branch_status_created_index');
            $table->index(['driver_user_id', 'status'], 'deliveries_driver_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
