<?php

use App\Http\Middleware\EnsureBranchAccess;
use App\Http\Middleware\EnsureBranchPermission;
use App\Modules\Kitchen\Http\Controllers\KitchenBoardController;
use App\Modules\Kitchen\Http\Controllers\KitchenStationController;
use App\Modules\Kitchen\Http\Controllers\KitchenTicketController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', EnsureBranchAccess::class])
    ->prefix('/admin/branches/{branch}/kitchen')
    ->name('admin.branches.kitchen.')
    ->group(function (): void {
        Route::get('/', KitchenBoardController::class)
            ->middleware(EnsureBranchPermission::class.':kitchen.view')
            ->name('board');

        Route::patch('/tickets/{ticket}/status', [KitchenTicketController::class, 'updateStatus'])
            ->whereNumber('ticket')
            ->middleware(EnsureBranchPermission::class.':kitchen.tickets.update')
            ->name('tickets.status');

        Route::get('/stations', [KitchenStationController::class, 'index'])
            ->middleware(EnsureBranchPermission::class.':kitchen.stations.manage')
            ->name('stations.index');
        Route::post('/stations', [KitchenStationController::class, 'store'])
            ->middleware(EnsureBranchPermission::class.':kitchen.stations.manage')
            ->name('stations.store');
        Route::put('/stations/{station}', [KitchenStationController::class, 'update'])
            ->whereNumber('station')
            ->middleware(EnsureBranchPermission::class.':kitchen.stations.manage')
            ->name('stations.update');
        Route::put('/stations/{station}/products', [KitchenStationController::class, 'syncProducts'])
            ->whereNumber('station')
            ->middleware(EnsureBranchPermission::class.':kitchen.stations.manage')
            ->name('stations.products.sync');
    });
