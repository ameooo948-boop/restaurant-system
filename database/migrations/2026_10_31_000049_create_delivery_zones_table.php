<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_zones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('city', 100);
            $table->string('area', 100)->nullable();
            $table->decimal('delivery_fee', 12, 2)->default(0);
            $table->decimal('minimum_order_amount', 12, 2)->default(0);
            $table->unsignedSmallInteger('estimated_minutes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->index(['branch_id', 'is_active', 'city'], 'delivery_zones_branch_active_city_index');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE delivery_zones ADD CONSTRAINT delivery_zones_amounts_nonnegative_check CHECK (delivery_fee >= 0 AND minimum_order_amount >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_zones');
    }
};
