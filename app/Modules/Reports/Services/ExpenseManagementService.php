<?php

namespace App\Modules\Reports\Services;

use App\Models\User;
use App\Modules\Branches\Models\Branch;
use App\Modules\Reports\Models\Expense;
use App\Modules\Reports\Models\ExpenseCategory;
use App\Shared\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExpenseManagementService
{
    public function create(Branch $branch, array $data, User $actor): Expense
    {
        return DB::transaction(function () use ($branch, $data, $actor): Expense {
            $lockedBranch = Branch::query()->lockForUpdate()->findOrFail($branch->id);
            $category = ExpenseCategory::query()
                ->where('restaurant_id', $lockedBranch->restaurant_id)
                ->where('is_active', true)
                ->lockForUpdate()
                ->findOrFail((int) $data['expense_category_id']);

            return Expense::query()->create([
                'branch_id' => $lockedBranch->id,
                'expense_category_id' => $category->id,
                'created_by' => $actor->id,
                'amount' => Money::fromMinor(Money::toMinor((string) $data['amount'])),
                'currency' => $lockedBranch->restaurant()->value('currency') ?: 'EGP',
                'expense_date' => $data['expense_date'],
                'payment_method' => $data['payment_method'],
                'payee' => $data['payee'] ?? null,
                'reference' => $data['reference'] ?? null,
                'description' => $data['description'],
                'status' => 'posted',
            ]);
        }, 3);
    }

    public function void(Branch $branch, int $expenseId, User $actor, string $reason): Expense
    {
        return DB::transaction(function () use ($branch, $expenseId, $actor, $reason): Expense {
            $expense = Expense::query()->where('branch_id', $branch->id)->lockForUpdate()->findOrFail($expenseId);
            if ($expense->status === 'voided') return $expense;

            if ($expense->status !== 'posted') {
                throw ValidationException::withMessages(['expense' => 'حالة المصروف الحالية لا تسمح بالإلغاء.']);
            }

            $expense->update([
                'status' => 'voided',
                'voided_by' => $actor->id,
                'voided_at' => now(),
                'void_reason' => $reason,
            ]);

            return $expense->fresh(['category', 'creator', 'voider']);
        }, 3);
    }
}
