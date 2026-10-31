<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kitchen_stations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 100);
            $table->string('description', 500)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->unique(['branch_id', 'code'], 'kitchen_stations_branch_code_unique');
            $table->index(['branch_id', 'is_active', 'sort_order'], 'kitchen_stations_branch_active_sort_index');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX kitchen_stations_one_default_per_branch_unique ON kitchen_stations (branch_id) WHERE is_default = true');
            DB::statement('ALTER TABLE kitchen_stations ADD CONSTRAINT kitchen_stations_sort_order_nonnegative CHECK (sort_order >= 0)');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS kitchen_stations_one_default_per_branch_unique');
        }

        Schema::dropIfExists('kitchen_stations');
    }
};
