<?php

namespace App\Modules\Orders\Actions;

use App\Models\User;
use App\Modules\Branches\Models\BranchSetting;
use App\Modules\Cart\Models\Cart;
use App\Modules\Cart\Services\CartPricingService;
use App\Modules\Delivery\Services\DeliveryZoneResolver;
use App\Modules\Kitchen\Actions\CreateKitchenTicketsAction;
use App\Modules\Orders\DTOs\CheckoutData;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\Payment;
use App\Modules\Payments\Services\PaymobCheckoutReadiness;
use App\Shared\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PlaceOrderAction
{
    public function __construct(
        private readonly CartPricingService $pricing,
        private readonly CreateKitchenTicketsAction $kitchenTickets,
        private readonly DeliveryZoneResolver $deliveryZones,
        private readonly PaymobCheckoutReadiness $paymobReadiness,
    ) {}

    public function execute(Cart $cart, CheckoutData $data, ?User $user): Order
    {
        return DB::transaction(function () use ($cart, $data, $user): Order {
            $lockedCart = Cart::query()->whereKey($cart->id)->lockForUpdate()->firstOrFail();

            if ($lockedCart->status === 'converted') {
                $existing = Order::query()->where('cart_id', $lockedCart->id)->with(['items.modifiers', 'payments'])->first();
                if ($existing) {
                    if ($existing->payment_method === 'cash') {
                        $this->kitchenTickets->execute($existing);
                    }
                    return $existing->load(['items.modifiers', 'payments', 'kitchenTickets']);
                }
            }

            if ($lockedCart->status !== 'active' || ($lockedCart->expires_at && $lockedCart->expires_at->isPast())) {
                throw ValidationException::withMessages(['cart' => 'السلة غير نشطة أو انتهت صلاحيتها.']);
            }
            if ($user && (int) $lockedCart->user_id !== (int) $user->id) {
                abort(404);
            }
            if ($user === null && ! $lockedCart->guest_token_hash) {
                abort(404);
            }

            $existingKey = Order::query()->where('idempotency_key', $data->idempotencyKey)->first();
            if ($existingKey) {
                if ((int) $existingKey->cart_id === (int) $lockedCart->id) {
                    if ($existingKey->payment_method === 'cash') {
                        $this->kitchenTickets->execute($existingKey);
                    }
                    return $existingKey->load(['items.modifiers', 'payments', 'kitchenTickets']);
                }
                throw ValidationException::withMessages(['idempotency_key' => 'مفتاح العملية مستخدم لطلب آخر. حدّث صفحة إتمام الطلب.']);
            }

            if ($lockedCart->items()->count() < 1) {
                throw ValidationException::withMessages(['cart' => 'السلة فارغة. أضف منتجات قبل إتمام الطلب.']);
            }

            if ($data->paymentMethod === 'vodafone_cash' && ! $this->paymobReadiness->isReady()) {
                throw ValidationException::withMessages([
                    'payment_method' => 'الدفع بفودافون كاش غير مفعل حاليًا. اختر الدفع النقدي أو حاول لاحقًا.',
                ]);
            }
            if (! in_array($data->paymentMethod, ['cash', 'vodafone_cash'], true)) {
                throw ValidationException::withMessages(['payment_method' => 'طريقة الدفع غير مدعومة.']);
            }

            $lockedCart->load(['branch.restaurant', 'items.variant.product', 'items.modifiers.option.group']);
            $summary = $this->pricing->summarize($lockedCart);
            $subtotalMinor = Money::toMinor($summary['subtotal']);
            $zoneResult = $data->orderType === 'delivery'
                ? $this->deliveryZones->resolve($lockedCart->branch, $data->deliveryAddress, $subtotalMinor)
                : ['zone' => null, 'fee_minor' => 0, 'zone_name' => null];
            $deliveryFeeMinor = (int) $zoneResult['fee_minor'];
            $taxMinor = 0;
            $discountMinor = 0;
            $totalMinor = $subtotalMinor - $discountMinor + $taxMinor + $deliveryFeeMinor;

            if ($deliveryFeeMinor < 0 || $totalMinor <= 0 || $totalMinor > 999_999_999_999) {
                throw ValidationException::withMessages(['cart' => 'إجمالي الطلب أو رسوم التوصيل غير صالحة.']);
            }

            $addressSnapshot = $data->deliveryAddress;
            if (is_array($addressSnapshot) && $zoneResult['zone_name']) {
                $addressSnapshot['delivery_zone_name'] = $zoneResult['zone_name'];
            }

            $order = Order::create([
                'public_id' => (string) Str::uuid(),
                'restaurant_id' => $lockedCart->branch->restaurant_id,
                'branch_id' => $lockedCart->branch_id,
                'delivery_zone_id' => $zoneResult['zone']?->id,
                'delivery_zone_name_snapshot' => $zoneResult['zone_name'],
                'cart_id' => $lockedCart->id,
                'user_id' => $user?->id,
                'customer_profile_id' => $user?->customerProfile?->id,
                'idempotency_key' => $data->idempotencyKey,
                'order_type' => $data->orderType,
                'order_status' => OrderStatus::PendingConfirmation->value,
                'payment_method' => $data->paymentMethod,
                'payment_status' => 'pending',
                'order_source' => 'website',
                'currency' => $lockedCart->currency,
                'subtotal' => Money::fromMinor($subtotalMinor),
                'discount_total' => Money::fromMinor($discountMinor),
                'tax_total' => Money::fromMinor($taxMinor),
                'delivery_fee' => Money::fromMinor($deliveryFeeMinor),
                'total' => Money::fromMinor($totalMinor),
                'customer_name' => $data->customerName,
                'customer_phone' => $data->customerPhone,
                'customer_email' => $data->customerEmail,
                'delivery_address_snapshot' => $addressSnapshot,
                'table_label' => $data->tableLabel,
                'customer_notes' => $data->customerNotes,
                'guest_token_hash' => $lockedCart->guest_token_hash,
                'placed_at' => now(),
            ]);

            $order->update([
                'order_number' => strtoupper($lockedCart->branch->code).'-'.now($lockedCart->branch->restaurant->timezone)->format('Ymd').'-'.str_pad((string) $order->id, 7, '0', STR_PAD_LEFT),
            ]);

            foreach ($lockedCart->items as $cartItem) {
                $selections = $cartItem->modifiers->map(fn ($modifier) => [
                    'modifier_option_id' => (int) $modifier->modifier_option_id,
                    'quantity' => (int) $modifier->quantity,
                ])->all();
                $resolved = $this->pricing->validateAndResolve($lockedCart->branch, $cartItem->variant, $selections);
                $lineMinor = $resolved['unit_price_minor'] * (int) $cartItem->quantity;
                $orderItem = $order->items()->create([
                    'product_id' => $resolved['product']->id,
                    'product_variant_id' => $resolved['variant']->id,
                    'product_name_snapshot' => $resolved['product']->name,
                    'variant_name_snapshot' => $resolved['variant']->name,
                    'sku_snapshot' => $resolved['variant']->sku,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => Money::fromMinor($resolved['unit_price_minor']),
                    'line_subtotal' => Money::fromMinor($lineMinor),
                    'notes' => $cartItem->notes,
                    'product_snapshot' => [
                        'product_name' => $resolved['product']->name,
                        'variant_name' => $resolved['variant']->name,
                    ],
                ]);

                foreach ($resolved['modifiers'] as $modifier) {
                    $modifierLineMinor = $modifier['unit_price_delta_minor'] * $modifier['quantity'] * (int) $cartItem->quantity;
                    $orderItem->modifiers()->create([
                        'modifier_option_id' => $modifier['option']->id,
                        'group_name_snapshot' => $modifier['group_name'],
                        'option_name_snapshot' => $modifier['option']->name,
                        'quantity' => $modifier['quantity'],
                        'unit_price_delta' => Money::fromMinor($modifier['unit_price_delta_minor']),
                        'line_total' => Money::fromMinor($modifierLineMinor),
                    ]);
                }
            }

            // Cash orders can enter the kitchen immediately. Online wallet orders wait for a verified Paymob callback.
            if ($data->paymentMethod === 'cash') {
                $this->kitchenTickets->execute($order);
            }

            $order->statusHistory()->create([
                'old_status' => null,
                'new_status' => OrderStatus::PendingConfirmation->value,
                'changed_by' => $user?->id,
                'source' => 'website_checkout',
                'note' => 'تم إنشاء الطلب من الموقع.',
                'created_at' => now(),
            ]);

            $order->payments()->create([
                'method' => $data->paymentMethod,
                'provider' => $data->paymentMethod === 'cash' ? 'cash' : 'paymob',
                'status' => 'pending',
                'amount' => Money::fromMinor($totalMinor),
                'currency' => $lockedCart->currency,
                'idempotency_key' => (string) Str::uuid(),
            ]);

            $lockedCart->update([
                'status' => 'converted',
                'active_owner_key' => null,
                'converted_at' => now(),
                'expires_at' => null,
            ]);

            return $order->load(['items.modifiers', 'payments', 'statusHistory', 'kitchenTickets']);
        }, 3);
    }

}
