<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE kitchen_tickets DROP CONSTRAINT IF EXISTS kitchen_tickets_status_check');
        DB::statement("ALTER TABLE kitchen_tickets ADD CONSTRAINT kitchen_tickets_status_check CHECK (status IN ('queued', 'preparing', 'ready', 'cancelled'))");

        DB::statement('ALTER TABLE kitchen_ticket_items DROP CONSTRAINT IF EXISTS kitchen_ticket_items_status_check');
        DB::statement("ALTER TABLE kitchen_ticket_items ADD CONSTRAINT kitchen_ticket_items_status_check CHECK (status IN ('queued', 'preparing', 'ready', 'cancelled'))");

        DB::statement('ALTER TABLE kitchen_ticket_status_histories DROP CONSTRAINT IF EXISTS kitchen_ticket_history_status_check');
        DB::statement("ALTER TABLE kitchen_ticket_status_histories ADD CONSTRAINT kitchen_ticket_history_status_check CHECK (new_status IN ('queued', 'preparing', 'ready', 'cancelled'))");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        $hasCancelledTickets = DB::table('kitchen_tickets')->where('status', 'cancelled')->exists();
        $hasCancelledItems = DB::table('kitchen_ticket_items')->where('status', 'cancelled')->exists();
        $hasCancelledHistory = DB::table('kitchen_ticket_status_histories')->where('new_status', 'cancelled')->exists();

        if ($hasCancelledTickets || $hasCancelledItems || $hasCancelledHistory) {
            throw new \RuntimeException('Cannot roll back cancelled kitchen states while cancellation records exist. Preserve operational history and keep this migration applied.');
        }

        DB::statement('ALTER TABLE kitchen_tickets DROP CONSTRAINT IF EXISTS kitchen_tickets_status_check');
        DB::statement("ALTER TABLE kitchen_tickets ADD CONSTRAINT kitchen_tickets_status_check CHECK (status IN ('queued', 'preparing', 'ready'))");
        DB::statement('ALTER TABLE kitchen_ticket_items DROP CONSTRAINT IF EXISTS kitchen_ticket_items_status_check');
        DB::statement("ALTER TABLE kitchen_ticket_items ADD CONSTRAINT kitchen_ticket_items_status_check CHECK (status IN ('queued', 'preparing', 'ready'))");
        DB::statement('ALTER TABLE kitchen_ticket_status_histories DROP CONSTRAINT IF EXISTS kitchen_ticket_history_status_check');
        DB::statement("ALTER TABLE kitchen_ticket_status_histories ADD CONSTRAINT kitchen_ticket_history_status_check CHECK (new_status IN ('queued', 'preparing', 'ready'))");
    }
};
