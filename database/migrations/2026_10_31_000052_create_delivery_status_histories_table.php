<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('delivery_id')->constrained('deliveries')->cascadeOnDelete();
            $table->string('old_status', 30)->nullable();
            $table->string('new_status', 30);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note', 500)->nullable();
            $table->timestampsTz();
            $table->index(['delivery_id', 'created_at'], 'delivery_status_histories_delivery_created_index');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE deliveries ADD CONSTRAINT deliveries_status_check CHECK (status IN ('pending_assignment', 'assigned', 'picked_up', 'delivered', 'failed', 'cancelled'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_status_histories');
    }
};
