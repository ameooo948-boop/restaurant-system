<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('restaurant_id')->constrained('restaurants')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('cart_id')->nullable()->unique()->constrained('carts')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('customer_profile_id')->nullable()->constrained('customer_profiles')->nullOnDelete();
            $table->string('order_number', 60)->nullable();
            $table->uuid('idempotency_key')->unique();
            $table->string('order_type', 20);
            $table->string('order_status', 30)->default('pending_confirmation');
            $table->string('payment_method', 30)->default('cash');
            $table->string('payment_status', 30)->default('pending');
            $table->string('order_source', 20)->default('website');
            $table->char('currency', 3)->default('EGP');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('delivery_fee', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->string('customer_name', 150);
            $table->string('customer_phone', 30);
            $table->string('customer_email')->nullable();
            $table->json('delivery_address_snapshot')->nullable();
            $table->string('table_label', 60)->nullable();
            $table->text('customer_notes')->nullable();
            $table->char('guest_token_hash', 64)->nullable();
            $table->timestampTz('placed_at');
            $table->timestampTz('confirmed_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->timestampsTz();

            $table->unique(['branch_id', 'order_number'], 'orders_branch_number_unique');
            $table->index(['branch_id', 'order_status', 'placed_at'], 'orders_branch_status_placed_index');
            $table->index(['user_id', 'placed_at'], 'orders_user_placed_index');
            $table->index(['branch_id', 'payment_status', 'placed_at'], 'orders_branch_payment_index');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_type_check CHECK (order_type IN ('delivery', 'pickup', 'dine_in'))");
            DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_status_check CHECK (order_status IN ('pending_confirmation', 'confirmed', 'preparing', 'ready', 'out_for_delivery', 'delivered', 'completed', 'cancelled'))");
            DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_payment_method_check CHECK (payment_method IN ('cash', 'vodafone_cash'))");
            DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_payment_status_check CHECK (payment_status IN ('pending', 'succeeded', 'failed', 'cancelled', 'refunded', 'partially_refunded'))");
            DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_amounts_nonnegative_check CHECK (subtotal >= 0 AND discount_total >= 0 AND tax_total >= 0 AND delivery_fee >= 0 AND total >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
