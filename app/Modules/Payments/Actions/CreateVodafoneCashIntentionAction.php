<?php

namespace App\Modules\Payments\Actions;

use App\Modules\Orders\Models\Payment;
use App\Modules\Payments\Exceptions\PaymentGatewayException;
use App\Modules\Payments\Services\EgyptianMobilePhoneNormalizer;
use App\Modules\Payments\Services\PaymobGatewayClient;
use App\Modules\Payments\Services\PaymobCheckoutReadiness;
use App\Modules\Payments\Services\PaymobUnifiedCheckoutUrlBuilder;
use App\Shared\Support\Money;
use Illuminate\Support\Facades\DB;

class CreateVodafoneCashIntentionAction
{
    public function __construct(
        private readonly PaymobGatewayClient $gateway,
        private readonly PaymobCheckoutReadiness $readiness,
        private readonly EgyptianMobilePhoneNormalizer $phoneNormalizer,
        private readonly PaymobUnifiedCheckoutUrlBuilder $checkoutUrlBuilder,
    ) {}

    public function execute(Payment $payment): string
    {
        try {
            return $this->createIntention($payment);
        } catch (PaymentGatewayException $exception) {
            // A local/configuration rejection is definitive: close this empty attempt so
            // the customer can retry after correcting configuration. Unknown network
            // outcomes remain pending and are deliberately not retryable automatically.
            if (! $exception->outcomeUnknown) {
                $this->markDefinitivePreflightFailure($payment);
            }

            throw $exception;
        }
    }

    private function createIntention(Payment $payment): string
    {
        $this->readiness->assertReady();
        $payment->loadMissing('order.branch', 'order.items');
        $order = $payment->order;

        if ($payment->method !== 'vodafone_cash' || $payment->provider !== 'paymob') {
            throw new PaymentGatewayException('وسيلة الدفع هذه ليست محاولة فودافون كاش عبر Paymob.');
        }
        if ($payment->status === 'succeeded' || $order->payment_status === 'succeeded') {
            throw new PaymentGatewayException('تم تحصيل قيمة هذا الطلب بالفعل.');
        }
        if (in_array($order->order_status, ['cancelled', 'completed'], true)) {
            throw new PaymentGatewayException('لا يمكن بدء الدفع لطلب ملغي أو مكتمل.');
        }
        if (($payment->metadata['gateway_state'] ?? null) === 'initiation_unknown') {
            throw new PaymentGatewayException(
                'نتيجة إنشاء محاولة الدفع السابقة غير معروفة. لن ننشئ محاولة أخرى تلقائيًا لتجنب تكرار التحصيل. يلزم التحقق من العملية لدى بوابة الدفع أو الدعم.',
                true,
            );
        }
        if ($payment->provider_reference || $payment->provider_intention_id || $payment->provider_order_id) {
            throw new PaymentGatewayException('تم إنشاء نية دفع لهذه المحاولة بالفعل. لا يمكن إعادة استخدام client_secret لأنه أحادي الاستخدام. استخدم صفحة الطلب لمعرفة الحالة.');
        }
        if ($payment->status !== 'pending') {
            throw new PaymentGatewayException('محاولة الدفع غير معلقة. أنشئ محاولة جديدة فقط بعد فشل مؤكد.');
        }

        $amountMinor = Money::toMinor((string) $payment->amount);
        if ($amountMinor <= 0) {
            throw new PaymentGatewayException('قيمة الدفع غير صالحة.');
        }

        $phone = $this->phoneNormalizer->normalize((string) $order->customer_phone);
        if (! $phone) {
            throw new PaymentGatewayException('رقم الهاتف يجب أن يكون رقم محمول مصريًا صحيحًا لبدء الدفع بفودافون كاش.');
        }

        $notificationUrl = trim((string) (config('paymob.notification_url') ?: route('webhooks.paymob.transactions', [], true)));

        $nameParts = preg_split('/\\s+/', trim((string) $order->customer_name), 2) ?: [];
        $firstName = $nameParts[0] ?? 'Customer';
        $lastName = $nameParts[1] ?? 'Restaurant';
        $address = $order->delivery_address_snapshot ?? [];
        $city = trim((string) ($address['city'] ?? $order->branch?->city ?? 'Cairo')) ?: 'Cairo';
        $street = trim((string) ($address['address_line'] ?? $order->branch?->address ?? 'NA')) ?: 'NA';

        $payload = [
            'amount' => $amountMinor,
            'currency' => $payment->currency ?: 'EGP',
            'payment_methods' => [(int) config('paymob.wallet_integration_id')],
            'items' => [[
                'name' => 'Restaurant order '.($order->order_number ?: $order->public_id),
                'amount' => $amountMinor,
                'description' => 'Online restaurant order',
                'quantity' => 1,
            ]],
            'billing_data' => [
                'apartment' => trim((string) ($address['apartment'] ?? 'NA')) ?: 'NA',
                'first_name' => $firstName,
                'last_name' => $lastName,
                'street' => $street,
                'building' => trim((string) ($address['building'] ?? 'NA')) ?: 'NA',
                'phone_number' => $phone,
                'city' => $city,
                'country' => 'EG',
                'email' => $order->customer_email ?: 'customer@example.com',
                'floor' => trim((string) ($address['floor'] ?? 'NA')) ?: 'NA',
                'state' => $city,
                'postal_code' => 'NA',
                'extra_description' => trim((string) ($address['landmark'] ?? 'NA')) ?: 'NA',
            ],
            'notification_url' => $notificationUrl,
            'special_reference' => (string) $order->public_id,
            'expiration' => (int) config('paymob.intention_expiration_seconds', 1800),
            'redirection_url' => route('store.orders.show', $order->public_id, true),
            'extras' => [
                'restaurant_order_public_id' => $order->public_id,
                'restaurant_payment_id' => (string) $payment->id,
            ],
        ];

        try {
            $intention = $this->gateway->createIntention($payload);
        } catch (PaymentGatewayException $exception) {
            $metadata = $payment->metadata ?? [];
            $metadata['gateway_state'] = $exception->outcomeUnknown ? 'initiation_unknown' : 'initiation_rejected';
            $payment->update([
                'status' => $exception->outcomeUnknown ? 'pending' : 'failed',
                'expires_at' => $exception->outcomeUnknown
                    ? now()->addSeconds((int) config('paymob.intention_expiration_seconds', 1800))
                    : null,
                'metadata' => $metadata,
            ]);
            if (! $exception->outcomeUnknown && ! $order->payments()->where('status', 'succeeded')->exists()) {
                $order->update(['payment_status' => 'failed']);
            }
            throw $exception;
        }

        $payment->update([
            'provider_reference' => (string) $intention['id'],
            'provider_intention_id' => (string) $intention['id'],
            'provider_order_id' => (string) $intention['intention_order_id'],
            'expires_at' => now()->addSeconds((int) config('paymob.intention_expiration_seconds', 1800)),
            'metadata' => array_merge($payment->metadata ?? [], [
                'gateway_state' => 'intention_created',
                'special_reference' => (string) $order->public_id,
            ]),
        ]);

        return $this->checkoutUrlBuilder->build(
            (string) config('paymob.base_url'),
            (string) config('paymob.public_key'),
            (string) $intention['client_secret'],
            (string) config('paymob.unified_checkout_path', '/unifiedcheckout/'),
        );
    }

    private function markDefinitivePreflightFailure(Payment $payment): void
    {
        if (! $payment->exists || ! $payment->getKey()) {
            return;
        }

        DB::transaction(function () use ($payment): void {
            $lockedPayment = Payment::query()->lockForUpdate()->find($payment->getKey());
            if (! $lockedPayment
                || $lockedPayment->method !== 'vodafone_cash'
                || $lockedPayment->provider !== 'paymob'
                || $lockedPayment->status !== 'pending'
                || data_get($lockedPayment->metadata, 'gateway_state') === 'initiation_unknown'
                || filled($lockedPayment->provider_intention_id)
                || filled($lockedPayment->provider_order_id)
                || filled($lockedPayment->provider_transaction_id)
                || filled($lockedPayment->provider_reference)) {
                return;
            }

            $metadata = $lockedPayment->metadata ?? [];
            $metadata['gateway_state'] = 'initiation_rejected';
            $metadata['failure_stage'] = 'local_preflight';

            $lockedPayment->update([
                'status' => 'failed',
                'expires_at' => null,
                'metadata' => $metadata,
            ]);

            $order = $lockedPayment->order()->lockForUpdate()->first();
            if ($order && ! $order->payments()->where('status', 'succeeded')->exists()) {
                $order->update(['payment_status' => 'failed']);
            }
        }, 3);
    }

}
