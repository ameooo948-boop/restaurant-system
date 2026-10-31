<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('old_status', 30)->nullable();
            $table->string('new_status', 30);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source', 30)->default('system');
            $table->text('note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['order_id', 'created_at'], 'order_status_histories_order_created_index');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE order_status_histories ADD CONSTRAINT order_status_histories_new_status_check CHECK (new_status IN ('pending_confirmation', 'confirmed', 'preparing', 'ready', 'out_for_delivery', 'delivered', 'completed', 'cancelled'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
    }
};
