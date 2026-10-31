<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kitchen_ticket_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kitchen_ticket_id')->constrained('kitchen_tickets')->cascadeOnDelete();
            $table->string('old_status', 20)->nullable();
            $table->string('new_status', 20);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source', 30)->default('kitchen_board');
            $table->text('note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['kitchen_ticket_id', 'created_at'], 'kitchen_ticket_history_ticket_created_index');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE kitchen_ticket_status_histories ADD CONSTRAINT kitchen_ticket_history_status_check CHECK (new_status IN ('queued', 'preparing', 'ready'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kitchen_ticket_status_histories');
    }
};
