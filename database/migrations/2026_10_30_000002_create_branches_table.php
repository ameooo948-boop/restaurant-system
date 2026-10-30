<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->restrictOnDelete();
            $table->string('code', 30);
            $table->string('name', 160);
            $table->string('slug', 180);
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->text('address_line')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('area', 100)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique(['restaurant_id', 'code']);
            $table->unique(['restaurant_id', 'slug']);
            $table->index(['restaurant_id', 'is_active']);
        });

        // PostgreSQL enforces at most one primary branch for each restaurant.
        // Application actions will also switch the primary branch in a transaction.
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement(
                'CREATE UNIQUE INDEX branches_single_primary_per_restaurant_unique '
                .'ON branches (restaurant_id) WHERE is_primary = TRUE AND deleted_at IS NULL'
            );
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS branches_single_primary_per_restaurant_unique');
        }

        Schema::dropIfExists('branches');
    }
};
