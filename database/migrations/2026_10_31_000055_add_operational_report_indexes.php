<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->index(['branch_id', 'placed_at', 'order_status'], 'orders_branch_date_status_report_idx');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->index(['status', 'paid_at'], 'payments_status_paid_report_idx');
        });

        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->index(['branch_id', 'movement_type', 'occurred_at'], 'stock_movements_report_idx');
        });

        Schema::table('deliveries', function (Blueprint $table): void {
            $table->index(['branch_id', 'delivered_at', 'status'], 'deliveries_report_idx');
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table): void {
            $table->dropIndex('deliveries_report_idx');
        });
        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->dropIndex('stock_movements_report_idx');
        });
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex('payments_status_paid_report_idx');
        });
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex('orders_branch_date_status_report_idx');
        });
    }
};
