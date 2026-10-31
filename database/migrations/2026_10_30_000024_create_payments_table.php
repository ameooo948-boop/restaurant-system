<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->string('method', 30);
            $table->string('provider', 40)->nullable();
            $table->string('provider_reference', 150)->nullable();
            $table->string('status', 30)->default('pending');
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('EGP');
            $table->uuid('idempotency_key')->unique();
            $table->json('metadata')->nullable();
            $table->timestampTz('paid_at')->nullable();
            $table->timestampsTz();

            $table->index(['order_id', 'status'], 'payments_order_status_index');
            $table->index(['provider', 'provider_reference'], 'payments_provider_reference_index');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_method_check CHECK (method IN ('cash', 'vodafone_cash'))");
            DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_status_check CHECK (status IN ('pending', 'succeeded', 'failed', 'cancelled', 'refunded', 'partially_refunded'))");
            DB::statement('ALTER TABLE payments ADD CONSTRAINT payments_amount_check CHECK (amount >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
