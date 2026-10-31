<?php

use App\Http\Middleware\EnsureBranchAccess;
use App\Http\Middleware\EnsureBranchPermission;
use App\Modules\Reports\Http\Controllers\ExpenseController;
use App\Modules\Reports\Http\Controllers\ReportsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->prefix('/admin/branches/{branch}')
    ->name('admin.branches.')
    ->middleware(EnsureBranchAccess::class)
    ->group(function (): void {
        Route::get('/reports', [ReportsController::class, 'index'])
            ->middleware(EnsureBranchPermission::class.':reports.view')
            ->name('reports.index');

        Route::get('/reports/export.csv', [ReportsController::class, 'export'])
            ->middleware(EnsureBranchPermission::class.':reports.view')
            ->name('reports.export');

        Route::get('/expenses', [ExpenseController::class, 'index'])
            ->middleware(EnsureBranchPermission::class.':expenses.manage')
            ->name('expenses.index');

        Route::post('/expenses', [ExpenseController::class, 'store'])
            ->middleware(EnsureBranchPermission::class.':expenses.manage')
            ->name('expenses.store');

        Route::patch('/expenses/{expense}/void', [ExpenseController::class, 'void'])
            ->middleware(EnsureBranchPermission::class.':expenses.manage')
            ->name('expenses.void');
    });
