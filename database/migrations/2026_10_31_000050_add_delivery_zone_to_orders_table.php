<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('delivery_zone_id')->nullable()->after('branch_id')->constrained('delivery_zones')->nullOnDelete();
            $table->string('delivery_zone_name_snapshot', 120)->nullable();
            $table->index(['branch_id', 'delivery_zone_id'], 'orders_branch_delivery_zone_index');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex('orders_branch_delivery_zone_index');
            $table->dropConstrainedForeignId('delivery_zone_id');
            $table->dropColumn('delivery_zone_name_snapshot');
        });
    }
};
