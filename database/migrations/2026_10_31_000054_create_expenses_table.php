<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('expense_category_id')->constrained('expense_categories')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('EGP');
            $table->date('expense_date');
            $table->string('payment_method', 24)->default('cash');
            $table->string('payee', 150)->nullable();
            $table->string('reference', 120)->nullable();
            $table->string('description', 500);
            $table->string('status', 16)->default('posted');
            $table->timestampTz('voided_at')->nullable();
            $table->string('void_reason', 500)->nullable();
            $table->timestampsTz();
            $table->index(['branch_id', 'expense_date', 'status'], 'expenses_branch_date_status_idx');
            $table->index(['expense_category_id', 'expense_date'], 'expenses_category_date_idx');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE expenses ADD CONSTRAINT expenses_amount_positive_ck CHECK (amount > 0)");
            DB::statement("ALTER TABLE expenses ADD CONSTRAINT expenses_payment_method_ck CHECK (payment_method IN ('cash', 'bank_transfer', 'card', 'wallet', 'other'))");
            DB::statement("ALTER TABLE expenses ADD CONSTRAINT expenses_status_ck CHECK (status IN ('posted', 'voided'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
