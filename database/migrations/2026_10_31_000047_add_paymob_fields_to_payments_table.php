<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('provider_intention_id', 120)->nullable();
            $table->string('provider_order_id', 120)->nullable();
            $table->string('provider_transaction_id', 120)->nullable();
            $table->timestampTz('expires_at')->nullable();
            $table->unique('provider_transaction_id', 'payments_provider_transaction_unique');
            $table->index(['provider', 'provider_order_id'], 'payments_provider_order_index');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropUnique('payments_provider_transaction_unique');
            $table->dropIndex('payments_provider_order_index');
            $table->dropColumn([
                'provider_intention_id', 'provider_order_id', 'provider_transaction_id',
                'expires_at',
            ]);
        });
    }
};
