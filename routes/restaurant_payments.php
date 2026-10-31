<?php

use App\Http\Middleware\EnsureBranchAccess;
use App\Http\Middleware\EnsureBranchPermission;
use App\Modules\Payments\Http\Controllers\PaymobWebhookController;
use App\Modules\Payments\Http\Controllers\RecordCashPaymentController;
use App\Modules\Payments\Http\Controllers\RetryVodafoneCashPaymentController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/paymob/transactions', PaymobWebhookController::class)
    ->name('webhooks.paymob.transactions');

Route::post('/orders/{publicId}/payments/vodafone-cash/retry', RetryVodafoneCashPaymentController::class)
    ->whereUuid('publicId')
    ->middleware('throttle:5,1')
    ->name('store.payments.vodafone_cash.retry');

Route::middleware(['auth', EnsureBranchAccess::class])
    ->prefix('/admin/branches/{branch}')
    ->group(function (): void {
        Route::post('/orders/{order}/payments/cash/record', RecordCashPaymentController::class)
            ->whereNumber('order')
            ->middleware(EnsureBranchPermission::class.':payments.record')
            ->name('admin.branches.orders.payments.cash.record');
    });
