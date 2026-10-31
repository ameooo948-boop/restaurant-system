<?php

use App\Http\Middleware\EnsureBranchAccess;
use App\Http\Middleware\EnsureBranchPermission;
use App\Modules\Orders\Http\Controllers\OrderManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', EnsureBranchAccess::class])
    ->prefix('/admin/branches/{branch}/orders')
    ->name('admin.branches.orders.')
    ->group(function (): void {
        Route::get('/', [OrderManagementController::class, 'index'])
            ->middleware(EnsureBranchPermission::class.':orders.view')
            ->name('index');

        Route::get('/{order}', [OrderManagementController::class, 'show'])
            ->whereNumber('order')
            ->middleware(EnsureBranchPermission::class.':orders.view')
            ->name('show');

        Route::patch('/{order}/status', [OrderManagementController::class, 'updateStatus'])
            ->whereNumber('order')
            ->middleware(EnsureBranchPermission::class.':orders.update_status')
            ->name('status');
    });
