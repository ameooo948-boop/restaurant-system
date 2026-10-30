<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('setting_key', 100);
            $table->json('setting_value')->nullable();
            $table->timestampsTz();

            $table->unique(['branch_id', 'setting_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_settings');
    }
};
