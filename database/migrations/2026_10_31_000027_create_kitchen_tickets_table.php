<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kitchen_tickets', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('kitchen_station_id')->constrained('kitchen_stations')->restrictOnDelete();
            $table->string('ticket_number', 120);
            $table->string('status', 20)->default('queued');
            $table->timestampTz('queued_at');
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('ready_at')->nullable();
            $table->timestampsTz();

            $table->unique(['order_id', 'kitchen_station_id'], 'kitchen_tickets_order_station_unique');
            $table->unique(['branch_id', 'ticket_number'], 'kitchen_tickets_branch_number_unique');
            $table->index(['branch_id', 'kitchen_station_id', 'status', 'queued_at'], 'kitchen_tickets_board_index');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE kitchen_tickets ADD CONSTRAINT kitchen_tickets_status_check CHECK (status IN ('queued', 'preparing', 'ready'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kitchen_tickets');
    }
};
