<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->char('guest_token_hash', 64)->nullable();
            $table->char('active_owner_key', 64)->nullable();
            $table->char('currency', 3)->default('EGP');
            $table->string('status', 20)->default('active');
            $table->timestampTz('expires_at')->nullable();
            $table->timestampTz('converted_at')->nullable();
            $table->timestampsTz();

            $table->unique(['branch_id', 'active_owner_key'], 'carts_branch_active_owner_unique');
            $table->index(['branch_id', 'status', 'expires_at'], 'carts_branch_status_expiry_index');
            $table->index(['user_id', 'status'], 'carts_user_status_index');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE carts ADD CONSTRAINT carts_status_check CHECK (status IN ('active', 'converted', 'expired', 'abandoned'))");
            DB::statement("ALTER TABLE carts ADD CONSTRAINT carts_owner_check CHECK ((user_id IS NOT NULL AND guest_token_hash IS NULL) OR (user_id IS NULL AND guest_token_hash IS NOT NULL))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
