<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurants', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 160);
            $table->string('slug', 180)->unique();
            $table->string('legal_name', 180)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('logo_path')->nullable();
            $table->char('currency', 3)->default('EGP');
            $table->string('timezone', 64)->default('Africa/Cairo');
            $table->string('default_locale', 10)->default('ar');
            $table->json('supported_locales')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};
