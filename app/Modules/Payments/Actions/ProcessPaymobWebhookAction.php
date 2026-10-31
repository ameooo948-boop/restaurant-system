<?php

namespace App\Modules\Payments\Actions;

use App\Modules\Kitchen\Actions\CreateKitchenTicketsAction;
use App\Modules\Orders\Models\Payment;
use App\Modules\Payments\Models\PaymentGatewayEvent;
use App\Modules\Payments\Services\PaymobVodafoneCashCallbackGuard;
use App\Shared\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ProcessPaymobWebhookAction
{
    public function __construct(
        private readonly CreateKitchenTicketsAction $kitchenTickets,
        private readonly PaymobVodafoneCashCallbackGuard $walletGuard,
    ) {}

    /** @return array{status:string} */
    public function execute(array $payload, string $rawBody): array
    {
        if (strtoupper((string) ($payload['type'] ?? '')) !== 'TRANSACTION') {
            throw ValidationException::withMessages(['webhook' => 'نوع Webhook غير مدعوم.']);
        }

        $transactionId = (string) data_get($payload, 'obj.id', '');
        $providerOrderId = (string) data_get($payload, 'obj.order.id', '');
        if ($transactionId === '' || $providerOrderId === '') {
            throw ValidationException::withMessages(['webhook' => 'بيانات المعاملة الواردة غير مكتملة.']);
        }

        $expectedIntegration = (int) config('paymob.wallet_integration_id');
        if ($expectedIntegration < 1 || (int) data_get($payload, 'obj.integration_id', 0) !== $expectedIntegration) {
            throw ValidationException::withMessages(['webhook' => 'معرّف تكامل الدفع لا يطابق إعداد المحفظة.']);
        }

        $payment = Payment::query()
            ->where('provider', 'paymob')
            ->where('method', 'vodafone_cash')
            ->where('provider_order_id', $providerOrderId)
            ->first();

        if (! $payment) {
            return ['status' => 'unmatched'];
        }

        $amountCents = (int) data_get($payload, 'obj.amount_cents', -1);
        if ($amountCents !== Money::toMinor((string) $payment->amount)
            || strtoupper((string) data_get($payload, 'obj.currency', '')) !== strtoupper((string) $payment->currency)) {
            throw ValidationException::withMessages(['webhook' => 'قيمة أو عملة المعاملة لا تطابق سجل الدفع.']);
        }

        // An Integration ID may represent the wider Mobile Wallets group. Enforce the exact subtype
        // observed for this merchant's Vodafone Cash test callback before changing any payment state.
        $this->walletGuard->assertExpectedWallet($payload);

        $success = data_get($payload, 'obj.success') === true || data_get($payload, 'obj.success') === 'true' || data_get($payload, 'obj.success') === 1;
        $pending = data_get($payload, 'obj.pending') === true || data_get($payload, 'obj.pending') === 'true' || data_get($payload, 'obj.pending') === 1;
        $errorOccurred = data_get($payload, 'obj.error_occured') === true || data_get($payload, 'obj.error_occured') === 'true' || data_get($payload, 'obj.error_occured') === 1;
        $eventState = ($success && ! $pending && ! $errorOccurred) ? 'success' : ((! $success && ! $pending) || $errorOccurred ? 'failed' : 'pending');

        return DB::transaction(function () use ($payment, $payload, $rawBody, $transactionId, $providerOrderId, $eventState, $success, $pending, $errorOccurred): array {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $order = $lockedPayment->order()->lockForUpdate()->firstOrFail();

            // Compatibility check for callbacks processed by the first 0.8.0 build, whose event_key included status.
            if (PaymentGatewayEvent::query()->where('provider', 'paymob')->where('event_id', $transactionId)->exists()) {
                return ['status' => 'duplicate'];
            }

            $inserted = DB::table('payment_gateway_events')->insertOrIgnore([
                'provider' => 'paymob',
                'event_key' => $transactionId,
                'event_id' => $transactionId,
                'event_type' => 'transaction_'.$eventState,
                'payment_id' => $lockedPayment->id,
                'provider_order_id' => $providerOrderId,
                'payload_sha256' => hash('sha256', $rawBody),
                'signature_verified' => true,
                'processed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($inserted === 0) {
                return ['status' => 'duplicate'];
            }

            if ($success && ! $pending && ! $errorOccurred) {
                if ($lockedPayment->status !== 'succeeded') {
                    $lockedPayment->update([
                        'status' => 'succeeded',
                        'provider_transaction_id' => $transactionId,
                        'paid_at' => now(),
                        'metadata' => array_merge($lockedPayment->metadata ?? [], ['gateway_state' => 'paid']),
                    ]);
                }

                $otherSuccessfulPayment = $order->payments()
                    ->where('status', 'succeeded')
                    ->where('id', '<>', $lockedPayment->id)
                    ->exists();
                $otherPendingAttempt = $order->payments()
                    ->where('method', 'vodafone_cash')
                    ->where('status', 'pending')
                    ->where('id', '<>', $lockedPayment->id)
                    ->exists();
                $lateForClosedOrder = in_array($order->order_status, ['cancelled', 'completed'], true);

                $order->update(['payment_status' => 'succeeded']);

                if ($otherSuccessfulPayment || $otherPendingAttempt || $lateForClosedOrder) {
                    $metadata = $lockedPayment->metadata ?? [];
                    $metadata['manual_review_required'] = true;
                    $metadata['manual_review_reason'] = match (true) {
                        $otherSuccessfulPayment => 'multiple_successful_payment_attempts',
                        $otherPendingAttempt => 'another_payment_attempt_is_still_pending',
                        default => 'payment_received_after_order_closed',
                    };
                    $lockedPayment->update(['metadata' => $metadata]);
                    Log::critical('Paymob payment succeeded but requires manual review; fulfillment was not triggered.', [
                        'order_id' => $order->id,
                        'payment_id' => $lockedPayment->id,
                        'transaction_id' => $transactionId,
                        'reason' => $metadata['manual_review_reason'],
                    ]);
                    return ['status' => 'manual_review'];
                }

                $this->kitchenTickets->execute($order->fresh(['items.modifiers']));
                return ['status' => 'succeeded'];
            }

            if (! $success && ! $pending || $errorOccurred) {
                if ($lockedPayment->status !== 'succeeded') {
                    $lockedPayment->update([
                        'status' => 'failed',
                        'provider_transaction_id' => $transactionId,
                        'metadata' => array_merge($lockedPayment->metadata ?? [], ['gateway_state' => 'failed']),
                    ]);
                }
                $latestWalletAttemptId = $order->payments()
                    ->where('method', 'vodafone_cash')
                    ->max('id');
                if ((int) $latestWalletAttemptId === (int) $lockedPayment->id
                    && ! $order->payments()->where('status', 'succeeded')->exists()) {
                    $order->update(['payment_status' => 'failed']);
                }
                return ['status' => 'failed'];
            }

            return ['status' => 'pending'];
        }, 3);
    }
}
