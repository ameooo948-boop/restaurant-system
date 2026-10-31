<?php

namespace Tests\Feature\Delivery;

use App\Models\User;
use App\Modules\Branches\Models\Branch;
use App\Modules\Delivery\Models\Delivery;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\OrderManagementService;
use App\Modules\Restaurants\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderCancellationDeliveryCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancelling_confirmed_order_also_cancels_unpicked_delivery_and_unassigns_driver(): void
    {
        $restaurant = Restaurant::query()->create([
            'name' => 'Delivery Cancel Test',
            'slug' => 'delivery-cancel-'.Str::lower(Str::random(6)),
            'currency' => 'EGP',
            'timezone' => 'Africa/Cairo',
            'default_locale' => 'ar',
            'supported_locales' => ['ar', 'en'],
            'is_active' => true,
        ]);
        $branch = Branch::query()->create([
            'restaurant_id' => $restaurant->id,
            'code' => 'D'.Str::upper(Str::random(4)),
            'name' => 'Delivery Branch',
            'slug' => 'delivery-branch-'.Str::lower(Str::random(6)),
            'is_primary' => true,
            'is_active' => true,
        ]);
        $actor = User::query()->create([
            'name' => 'Branch Manager',
            'email' => 'delivery-cancel-'.Str::lower(Str::random(6)).'@example.test',
            'password' => 'StrongPassword!123',
            'is_active' => true,
        ]);
        $order = Order::query()->create([
            'public_id' => (string) Str::uuid(),
            'restaurant_id' => $restaurant->id,
            'branch_id' => $branch->id,
            'idempotency_key' => (string) Str::uuid(),
            'order_type' => 'delivery',
            'order_status' => 'confirmed',
            'payment_method' => 'cash',
            'payment_status' => 'pending',
            'order_source' => 'website',
            'currency' => 'EGP',
            'subtotal' => '100.00',
            'discount_total' => '0.00',
            'tax_total' => '0.00',
            'delivery_fee' => '10.00',
            'total' => '110.00',
            'customer_name' => 'Customer',
            'customer_phone' => '01012345678',
            'placed_at' => now(),
            'confirmed_at' => now(),
        ]);
        $delivery = Delivery::query()->create([
            'order_id' => $order->id,
            'branch_id' => $branch->id,
            'driver_user_id' => $actor->id,
            'status' => 'assigned',
            'assigned_at' => now(),
        ]);

        app(OrderManagementService::class)->transition(
            $branch->id,
            $order->id,
            'cancelled',
            $actor,
            'إلغاء اختباري قبل استلام المندوب.',
        );

        $this->assertSame('cancelled', $delivery->fresh()->status);
        $this->assertNull($delivery->fresh()->driver_user_id);
        $this->assertDatabaseHas('delivery_status_histories', [
            'delivery_id' => $delivery->id,
            'old_status' => 'assigned',
            'new_status' => 'cancelled',
            'changed_by' => $actor->id,
        ]);
    }
}
