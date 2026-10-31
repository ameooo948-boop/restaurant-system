<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateway_events', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 40);
            $table->string('event_key', 180);
            $table->string('event_id', 120)->nullable();
            $table->string('event_type', 60);
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->string('provider_order_id', 120)->nullable();
            $table->char('payload_sha256', 64);
            $table->boolean('signature_verified')->default(false);
            $table->timestampTz('processed_at')->nullable();
            $table->timestampsTz();
            $table->unique(['provider', 'event_key'], 'payment_gateway_events_provider_key_unique');
            $table->index(['provider', 'event_id'], 'payment_gateway_events_event_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_events');
    }
};
