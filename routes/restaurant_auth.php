<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\BranchDashboardController;
use App\Http\Middleware\EnsureBranchAccess;
use App\Http\Middleware\EnsureBranchPermission;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('/account', AccountController::class)->name('account.index');

    Route::prefix('/admin/branches/{branch}')
        ->name('admin.branches.')
        ->middleware(EnsureBranchAccess::class)
        ->group(function (): void {
            Route::get('/dashboard', BranchDashboardController::class)
                ->middleware(EnsureBranchPermission::class.':dashboard.view')
                ->name('dashboard');
        });
});
