<?php

use App\Http\Middleware\EnsureBranchAccess;
use App\Http\Middleware\EnsureBranchPermission;
use App\Modules\Delivery\Http\Controllers\DeliveryController;
use App\Modules\Delivery\Http\Controllers\DeliveryZoneController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', EnsureBranchAccess::class])
    ->prefix('/admin/branches/{branch}')
    ->group(function (): void {
        Route::get('/deliveries', [DeliveryController::class, 'index'])
            ->middleware(EnsureBranchPermission::class.':deliveries.view')
            ->name('admin.branches.deliveries.index');
        Route::post('/deliveries/{delivery}/assign', [DeliveryController::class, 'assign'])
            ->whereNumber('delivery')
            ->middleware(EnsureBranchPermission::class.':deliveries.assign')
            ->name('admin.branches.deliveries.assign');
        Route::patch('/deliveries/{delivery}/status', [DeliveryController::class, 'updateStatus'])
            ->whereNumber('delivery')
            ->middleware(EnsureBranchPermission::class.':deliveries.update')
            ->name('admin.branches.deliveries.status');

        Route::get('/delivery-zones', [DeliveryZoneController::class, 'index'])
            ->middleware(EnsureBranchPermission::class.':deliveries.zones.manage')
            ->name('admin.branches.delivery-zones.index');
        Route::post('/delivery-zones', [DeliveryZoneController::class, 'store'])
            ->middleware(EnsureBranchPermission::class.':deliveries.zones.manage')
            ->name('admin.branches.delivery-zones.store');
        Route::put('/delivery-zones/{zone}', [DeliveryZoneController::class, 'update'])
            ->whereNumber('zone')
            ->middleware(EnsureBranchPermission::class.':deliveries.zones.manage')
            ->name('admin.branches.delivery-zones.update');
        Route::delete('/delivery-zones/{zone}', [DeliveryZoneController::class, 'destroy'])
            ->whereNumber('zone')
            ->middleware(EnsureBranchPermission::class.':deliveries.zones.manage')
            ->name('admin.branches.delivery-zones.destroy');
    });
