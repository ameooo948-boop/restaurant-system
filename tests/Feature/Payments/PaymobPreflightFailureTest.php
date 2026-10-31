<?php

namespace Tests\Feature\Payments;

use App\Models\User;
use App\Modules\Branches\Models\Branch;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\Payment;
use App\Modules\Payments\Actions\CreateVodafoneCashIntentionAction;
use App\Modules\Payments\Exceptions\PaymentGatewayException;
use App\Modules\Payments\Services\PaymentAttemptRetryPolicy;
use App\Modules\Restaurants\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymobPreflightFailureTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_configuration_rejection_closes_empty_attempt_and_allows_safe_retry(): void
    {
        config(['paymob.enabled' => false]);
        [$restaurant, $branch] = $this->makeBranch();
        $order = Order::query()->create([
            'public_id' => (string) Str::uuid(),
            'restaurant_id' => $restaurant->id,
            'branch_id' => $branch->id,
            'idempotency_key' => (string) Str::uuid(),
            'order_type' => 'pickup',
            'order_status' => 'pending_confirmation',
            'payment_method' => 'vodafone_cash',
            'payment_status' => 'pending',
            'order_source' => 'website',
            'currency' => 'EGP',
            'subtotal' => '120.00',
            'discount_total' => '0.00',
            'tax_total' => '0.00',
            'delivery_fee' => '0.00',
            'total' => '120.00',
            'customer_name' => 'Test Customer',
            'customer_phone' => '01012345678',
            'placed_at' => now(),
        ]);
        $payment = $order->payments()->create([
            'method' => 'vodafone_cash',
            'provider' => 'paymob',
            'status' => 'pending',
            'amount' => '120.00',
            'currency' => 'EGP',
            'idempotency_key' => (string) Str::uuid(),
        ]);

        try {
            app(CreateVodafoneCashIntentionAction::class)->execute($payment);
            $this->fail('Expected Paymob configuration rejection.');
        } catch (PaymentGatewayException $exception) {
            $this->assertFalse($exception->outcomeUnknown);
        }

        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertSame('initiation_rejected', data_get($payment->fresh()->metadata, 'gateway_state'));
        $this->assertSame('local_preflight', data_get($payment->fresh()->metadata, 'failure_stage'));
        $this->assertSame('failed', $order->fresh()->payment_status);
        $this->assertTrue(app(PaymentAttemptRetryPolicy::class)->mayCreateNewAttempt($payment->fresh()));
    }

    private function makeBranch(): array
    {
        $restaurant = Restaurant::query()->create([
            'name' => 'Preflight Test',
            'slug' => 'preflight-test-'.Str::lower(Str::random(6)),
            'currency' => 'EGP',
            'timezone' => 'Africa/Cairo',
            'default_locale' => 'ar',
            'supported_locales' => ['ar', 'en'],
            'is_active' => true,
        ]);
        $branch = Branch::query()->create([
            'restaurant_id' => $restaurant->id,
            'code' => 'P'.Str::upper(Str::random(4)),
            'name' => 'Preflight Branch',
            'slug' => 'preflight-branch-'.Str::lower(Str::random(6)),
            'is_primary' => true,
            'is_active' => true,
        ]);
        return [$restaurant, $branch];
    }
}
