<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_user_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->unique(['branch_id', 'user_id'], 'branch_user_assignments_branch_user_unique');
            $table->index(['user_id', 'is_active'], 'branch_user_assignments_user_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_user_assignments');
    }
};
