<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kitchen_ticket_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kitchen_ticket_id')->constrained('kitchen_tickets')->cascadeOnDelete();
            $table->foreignId('order_item_id')->unique()->constrained('order_items')->restrictOnDelete();
            $table->string('status', 20)->default('queued');
            $table->string('product_name_snapshot', 160);
            $table->string('variant_name_snapshot', 100);
            $table->unsignedSmallInteger('quantity');
            $table->json('modifiers_snapshot')->nullable();
            $table->text('notes_snapshot')->nullable();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('ready_at')->nullable();
            $table->timestampsTz();

            $table->index(['kitchen_ticket_id', 'status'], 'kitchen_ticket_items_ticket_status_index');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE kitchen_ticket_items ADD CONSTRAINT kitchen_ticket_items_status_check CHECK (status IN ('queued', 'preparing', 'ready'))");
            DB::statement('ALTER TABLE kitchen_ticket_items ADD CONSTRAINT kitchen_ticket_items_quantity_check CHECK (quantity > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kitchen_ticket_items');
    }
};
