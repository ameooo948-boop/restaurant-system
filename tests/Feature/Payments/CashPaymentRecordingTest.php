<?php

namespace Tests\Feature\Payments;

use App\Models\User;
use App\Modules\Branches\Models\Branch;
use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Actions\RecordCashPaymentAction;
use App\Modules\Payments\Exceptions\PaymentGatewayException;
use App\Modules\Restaurants\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CashPaymentRecordingTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_payment_can_be_recorded_once_and_is_idempotent(): void
    {
        [$restaurant, $branch] = $this->makeBranch();
        $actor = User::query()->create([
            'name' => 'Cashier', 'email' => 'cashier@example.test', 'password' => 'StrongPassword!123', 'is_active' => true,
        ]);
        $order = $this->makeOrder($restaurant->id, $branch->id, 'cash');
        $payment = $order->payments()->create([
            'method' => 'cash', 'provider' => 'cash', 'status' => 'pending',
            'amount' => '120.00', 'currency' => 'EGP', 'idempotency_key' => (string) Str::uuid(),
        ]);

        $action = app(RecordCashPaymentAction::class);
        $result = $action->execute($branch->id, $order->id, $actor, 'تم التحصيل في المطعم.');
        $again = $action->execute($branch->id, $order->id, $actor, 'محاولة تكرار آمنة.');

        $this->assertSame($payment->id, $result->id);
        $this->assertSame('succeeded', $again->status);
        $this->assertSame('succeeded', $order->fresh()->payment_status);
        $this->assertSame($actor->id, $again->metadata['cash_collection']['recorded_by']);
    }

    public function test_cash_collection_action_rejects_online_wallet_orders(): void
    {
        [$restaurant, $branch] = $this->makeBranch();
        $actor = User::query()->create([
            'name' => 'Cashier', 'email' => 'cashier-wallet@example.test', 'password' => 'StrongPassword!123', 'is_active' => true,
        ]);
        $order = $this->makeOrder($restaurant->id, $branch->id, 'vodafone_cash');

        $this->expectException(ValidationException::class);
        app(RecordCashPaymentAction::class)->execute($branch->id, $order->id, $actor);
    }

    private function makeBranch(): array
    {
        $restaurant = Restaurant::query()->create([
            'name' => 'Payment Test', 'slug' => 'payment-test-'.Str::lower(Str::random(6)),
            'currency' => 'EGP', 'timezone' => 'Africa/Cairo', 'default_locale' => 'ar',
            'supported_locales' => ['ar', 'en'], 'is_active' => true,
        ]);
        $branch = Branch::query()->create([
            'restaurant_id' => $restaurant->id, 'code' => 'P'.Str::upper(Str::random(4)),
            'name' => 'Payment Branch', 'slug' => 'payment-branch-'.Str::lower(Str::random(6)),
            'is_primary' => true, 'is_active' => true,
        ]);
        return [$restaurant, $branch];
    }

    private function makeOrder(int $restaurantId, int $branchId, string $method): Order
    {
        return Order::query()->create([
            'public_id' => (string) Str::uuid(), 'restaurant_id' => $restaurantId, 'branch_id' => $branchId,
            'idempotency_key' => (string) Str::uuid(), 'order_type' => 'pickup',
            'order_status' => 'pending_confirmation', 'payment_method' => $method,
            'payment_status' => 'pending', 'order_source' => 'website', 'currency' => 'EGP',
            'subtotal' => '120.00', 'discount_total' => '0.00', 'tax_total' => '0.00',
            'delivery_fee' => '0.00', 'total' => '120.00', 'customer_name' => 'Test Customer',
            'customer_phone' => '01000000000', 'placed_at' => now(),
        ]);
    }
}
