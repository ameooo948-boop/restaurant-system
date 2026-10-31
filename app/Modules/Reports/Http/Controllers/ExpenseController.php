<?php

namespace App\Modules\Reports\Http\Controllers;

use App\Modules\Branches\Models\Branch;
use App\Modules\Reports\Http\Requests\StoreExpenseRequest;
use App\Modules\Reports\Http\Requests\VoidExpenseRequest;
use App\Modules\Reports\Models\Expense;
use App\Modules\Reports\Models\ExpenseCategory;
use App\Modules\Reports\Services\ExpenseManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController
{
    public function index(Branch $branch): Response
    {
        $expenses = Expense::query()->where('branch_id', $branch->id)
            ->with(['category', 'creator', 'voider'])
            ->orderByDesc('expense_date')->orderByDesc('id')->paginate(30)->withQueryString();

        $monthStart = now()->startOfMonth()->toDateString();
        $today = now()->toDateString();
        $summary = DB::table('expenses')->where('branch_id', $branch->id)->where('status', 'posted')
            ->whereBetween('expense_date', [$monthStart, $today])
            ->selectRaw('COUNT(*) AS count, COALESCE(SUM(amount), 0) AS amount')->first();

        return Inertia::render('Reports/Expenses/Index', [
            'branch' => $branch->only(['id', 'code', 'name']),
            'currency' => $branch->restaurant?->currency ?: 'EGP',
            'categories' => ExpenseCategory::query()->where('restaurant_id', $branch->restaurant_id)
                ->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'expenses' => $expenses,
            'summary' => ['count' => (int) ($summary->count ?? 0), 'amount' => (float) ($summary->amount ?? 0)],
        ]);
    }

    public function store(StoreExpenseRequest $request, Branch $branch, ExpenseManagementService $expenses): RedirectResponse
    {
        $expenses->create($branch, $request->validated(), $request->user());

        return back()->with('success', 'تم تسجيل المصروف بنجاح.');
    }

    public function void(VoidExpenseRequest $request, Branch $branch, Expense $expense, ExpenseManagementService $expenses): RedirectResponse
    {
        $expenses->void($branch, $expense->id, $request->user(), $request->validated('reason'));

        return back()->with('success', 'تم إلغاء المصروف مع الاحتفاظ بسجله.');
    }
}
