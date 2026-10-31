<?php

use App\Http\Middleware\EnsureStoreBranchAvailable;
use App\Modules\Cart\Http\Controllers\StoreCartController;
use App\Modules\Menu\Http\Controllers\StoreMenuController;
use App\Modules\Orders\Http\Controllers\OrderTrackingController;
use App\Modules\Orders\Http\Controllers\StoreCheckoutController;
use Illuminate\Support\Facades\Route;

Route::middleware(EnsureStoreBranchAvailable::class)
    ->prefix('/branches/{branch}')
    ->where(['branch' => '[0-9]+'])
    ->group(function (): void {
        Route::get('/menu', [StoreMenuController::class, 'index'])
            ->name('store.menu.index');

        Route::get('/cart', [StoreCartController::class, 'show'])
            ->name('store.cart.show');
        Route::post('/cart/items', [StoreCartController::class, 'store'])
            ->middleware('throttle:30,1')
            ->name('store.cart.items.store');
        Route::patch('/cart/items/{cartItem}', [StoreCartController::class, 'update'])
            ->whereNumber('cartItem')
            ->middleware('throttle:30,1')
            ->name('store.cart.items.update');
        Route::delete('/cart/items/{cartItem}', [StoreCartController::class, 'destroy'])
            ->whereNumber('cartItem')
            ->name('store.cart.items.destroy');

        Route::get('/checkout', [StoreCheckoutController::class, 'create'])
            ->name('store.checkout.create');
        Route::post('/checkout', [StoreCheckoutController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('store.checkout.store');
    });

Route::get('/orders/{publicId}', [OrderTrackingController::class, 'show'])
    ->whereUuid('publicId')
    ->middleware('throttle:30,1')
    ->name('store.orders.show');
