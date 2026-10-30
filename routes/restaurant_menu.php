<?php

use App\Http\Middleware\EnsureBranchAccess;
use App\Http\Middleware\EnsureBranchPermission;
use App\Modules\Menu\Http\Controllers\MenuCategoryController;
use App\Modules\Menu\Http\Controllers\ModifierGroupController;
use App\Modules\Menu\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', EnsureBranchAccess::class])
    ->prefix('/admin/branches/{branch}/menu')
    ->name('admin.branches.menu.')
    ->group(function (): void {
        Route::get('/categories', [MenuCategoryController::class, 'index'])
            ->middleware(EnsureBranchPermission::class.':menu.view')
            ->name('categories.index');
        Route::post('/categories', [MenuCategoryController::class, 'store'])
            ->middleware(EnsureBranchPermission::class.':menu.catalog.manage')
            ->name('categories.store');
        Route::put('/categories/{category}', [MenuCategoryController::class, 'update'])
            ->whereNumber('category')
            ->middleware(EnsureBranchPermission::class.':menu.catalog.manage')
            ->name('categories.update');
        Route::delete('/categories/{category}', [MenuCategoryController::class, 'destroy'])
            ->whereNumber('category')
            ->middleware(EnsureBranchPermission::class.':menu.catalog.manage')
            ->name('categories.destroy');

        Route::get('/products', [ProductController::class, 'index'])
            ->middleware(EnsureBranchPermission::class.':menu.view')
            ->name('products.index');
        Route::post('/products', [ProductController::class, 'store'])
            ->middleware(EnsureBranchPermission::class.':menu.catalog.manage')
            ->name('products.store');
        Route::put('/products/{product}', [ProductController::class, 'update'])
            ->whereNumber('product')
            ->middleware(EnsureBranchPermission::class.':menu.catalog.manage')
            ->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])
            ->whereNumber('product')
            ->middleware(EnsureBranchPermission::class.':menu.catalog.manage')
            ->name('products.destroy');

        Route::get('/modifiers', [ModifierGroupController::class, 'index'])
            ->middleware(EnsureBranchPermission::class.':menu.view')
            ->name('modifiers.index');
        Route::post('/modifiers', [ModifierGroupController::class, 'store'])
            ->middleware(EnsureBranchPermission::class.':menu.catalog.manage')
            ->name('modifiers.store');
        Route::put('/modifiers/{group}', [ModifierGroupController::class, 'update'])
            ->whereNumber('group')
            ->middleware(EnsureBranchPermission::class.':menu.catalog.manage')
            ->name('modifiers.update');
        Route::delete('/modifiers/{group}', [ModifierGroupController::class, 'destroy'])
            ->whereNumber('group')
            ->middleware(EnsureBranchPermission::class.':menu.catalog.manage')
            ->name('modifiers.destroy');
    });
