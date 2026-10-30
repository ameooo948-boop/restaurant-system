<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_special_hours', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->date('service_date');
            $table->string('label', 120)->nullable();
            $table->time('opens_at')->nullable();
            $table->time('closes_at')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->timestampsTz();

            $table->unique(['branch_id', 'service_date']);
            $table->index('service_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_special_hours');
    }
};
