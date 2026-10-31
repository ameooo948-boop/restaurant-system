<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Modules\Branches\Models\Branch;
use App\Modules\Cart\Models\Cart;
use App\Modules\Cart\Services\CartManager;
use App\Modules\Cart\Services\CartPricingService;
use App\Modules\Orders\Actions\PlaceOrderAction;
use App\Modules\Orders\DTOs\CheckoutData;
use App\Modules\Orders\Http\Requests\CheckoutRequest;
use App\Modules\Payments\Actions\CreateVodafoneCashIntentionAction;
use App\Modules\Payments\Exceptions\PaymentGatewayException;
use App\Modules\Payments\Services\PaymobCheckoutReadiness;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class StoreCheckoutController
{
    public function create(Branch $branch, Request $request, CartManager $manager, CartPricingService $pricing, PaymobCheckoutReadiness $paymobReadiness): Response|RedirectResponse
    {
        $cart = $manager->resolve($branch, $request);
        $manager->assertOwnedByRequest($cart, $request);
        $summary = $pricing->summarize($cart);

        if ($summary['items_count'] < 1) {
            return redirect()->route('store.cart.show', $branch)->with('error', 'السلة فارغة. أضف منتجات أولاً.');
        }

        $profile = $request->user()?->customerProfile;
        $walletEnabled = $paymobReadiness->isReady();

        return Inertia::render('Store/Checkout/Create', [
            'branch' => $branch->only(['id', 'name', 'code']),
            'cart' => $summary,
            'cartPublicId' => $cart->public_id,
            'idempotencyKey' => (string) Str::uuid(),
            'customer' => [
                'name' => $profile?->full_name ?? $request->user()?->name ?? '',
                'phone' => $profile?->phone ?? '',
                'email' => $request->user()?->email ?? '',
            ],
            'paymentMethods' => [
                ['value' => 'cash', 'label' => 'نقدًا عند الاستلام أو في المطعم', 'enabled' => true],
                ['value' => 'vodafone_cash', 'label' => 'فودافون كاش', 'enabled' => $walletEnabled],
            ],
            'vodafoneCashNotice' => $walletEnabled
                ? 'سيتم تحويلك إلى صفحة الدفع الآمنة التابعة لـ Paymob لإتمام الدفع بمحفظة فودافون كاش.'
                : 'فودافون كاش غير متاح حاليًا حتى تفعيل حساب التاجر وربط تكامل المحافظ.',
        ]);
    }

    public function store(
        Branch $branch,
        CheckoutRequest $request,
        CartManager $manager,
        PlaceOrderAction $action,
        CreateVodafoneCashIntentionAction $walletPayment,
    ): RedirectResponse {
        $data = $request->validated();
        $cart = Cart::query()->where('branch_id', $branch->id)
            ->where('public_id', $data['cart_public_id'])->firstOrFail();
        $manager->assertOwnedByRequest($cart, $request);

        $address = $data['order_type'] === 'delivery' ? array_filter([
            'address_line' => $data['address_line'] ?? null,
            'city' => $data['city'] ?? null,
            'area' => $data['area'] ?? null,
            'building' => $data['building'] ?? null,
            'floor' => $data['floor'] ?? null,
            'apartment' => $data['apartment'] ?? null,
            'landmark' => $data['landmark'] ?? null,
        ], fn ($value) => $value !== null && $value !== '') : null;

        $checkout = new CheckoutData(
            idempotencyKey: $data['idempotency_key'],
            orderType: $data['order_type'],
            paymentMethod: $data['payment_method'],
            customerName: trim($data['customer_name']),
            customerPhone: trim($data['customer_phone']),
            customerEmail: isset($data['customer_email']) ? trim($data['customer_email']) : null,
            deliveryAddress: $address,
            tableLabel: $data['table_label'] ?? null,
            customerNotes: $data['customer_notes'] ?? null,
        );

        $order = $action->execute($cart, $checkout, $request->user());

        if ($data['payment_method'] === 'vodafone_cash') {
            $payment = $order->payments()->where('method', 'vodafone_cash')->latest('id')->firstOrFail();
            try {
                return redirect()->away($walletPayment->execute($payment));
            } catch (PaymentGatewayException $exception) {
                return redirect()->route('store.orders.show', $order->public_id)
                    ->with('error', $exception->getMessage());
            }
        }

        return redirect()->route('store.orders.show', $order->public_id)
            ->with('success', 'تم استلام طلبك بنجاح. رقم الطلب: '.$order->order_number);
    }
}
