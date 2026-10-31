<?php

namespace App\Modules\Payments\Http\Controllers;

use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\Payment;
use App\Modules\Payments\Actions\CreateVodafoneCashIntentionAction;
use App\Modules\Payments\Exceptions\PaymentGatewayException;
use App\Modules\Payments\Services\PaymentAttemptRetryPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RetryVodafoneCashPaymentController
{
    public function __invoke(
        string $publicId,
        Request $request,
        CreateVodafoneCashIntentionAction $action,
        PaymentAttemptRetryPolicy $retryPolicy,
    ): RedirectResponse {
        $result = DB::transaction(function () use ($publicId, $request, $retryPolicy): array {
            $order = Order::query()->where('public_id', $publicId)->lockForUpdate()->firstOrFail();
            $this->assertOwner($order, $request);

            abort_unless($order->payment_method === 'vodafone_cash', 404);
            abort_if(in_array($order->order_status, ['cancelled', 'completed'], true), 409);
            abort_if($order->payment_status === 'succeeded', 409, 'تم دفع هذا الطلب بالفعل.');

            $latest = $order->payments()->where('method', 'vodafone_cash')->latest('id')->lockForUpdate()->first();
            if (! $retryPolicy->mayCreateNewAttempt($latest)) {
                return [
                    'payment' => null,
                    'message' => 'لا يمكن بدء محاولة جديدة الآن. يلزم وصول فشل مؤكد من Paymob للمحاولة السابقة؛ انتهاء الوقت وحده لا يثبت أن الدفع لم يتم.',
                ];
            }

            $payment = $order->payments()->create([
                'method' => 'vodafone_cash',
                'provider' => 'paymob',
                'status' => 'pending',
                'amount' => $order->total,
                'currency' => $order->currency,
                'idempotency_key' => (string) Str::uuid(),
            ]);
            $order->update(['payment_status' => 'pending']);

            return ['payment' => $payment, 'message' => null];
        }, 3);

        if (! $result['payment'] instanceof Payment) {
            return redirect()->route('store.orders.show', $publicId)->with('error', $result['message']);
        }

        try {
            return redirect()->away($action->execute($result['payment']));
        } catch (PaymentGatewayException $exception) {
            return redirect()->route('store.orders.show', $publicId)->with('error', $exception->getMessage());
        }
    }

    private function assertOwner(Order $order, Request $request): void
    {
        $user = $request->user();
        $isUserOwner = $user && (int) $order->user_id === (int) $user->id;
        $guestToken = $request->session()->get('restaurant.guest_cart_token');
        $isGuestOwner = $order->user_id === null && is_string($guestToken)
            && is_string($order->guest_token_hash)
            && hash_equals($order->guest_token_hash, hash('sha256', $guestToken));

        abort_unless($isUserOwner || $isGuestOwner, 404);
    }
}
