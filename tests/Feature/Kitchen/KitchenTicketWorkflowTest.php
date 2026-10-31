<?php

namespace Tests\Feature\Kitchen;

use App\Models\User;
use App\Modules\Branches\Models\Branch;
use App\Modules\Kitchen\Actions\CreateKitchenTicketsAction;
use App\Modules\Kitchen\Models\KitchenTicket;
use App\Modules\Kitchen\Services\KitchenTicketService;
use App\Modules\Menu\Models\Product;
use App\Modules\Orders\Models\Order;
use App\Modules\Restaurants\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class KitchenTicketWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_preparing_and_ready_transitions_update_order_history(): void
    {
        [$branch, $order, $user] = $this->makeOrder();
        app(CreateKitchenTicketsAction::class)->execute($order);
        $ticket = KitchenTicket::query()->where('order_id', $order->id)->firstOrFail();
        $service = app(KitchenTicketService::class);

        $service->transition($branch->id, $ticket->id, 'preparing', $user);
        $this->assertSame('preparing', $order->fresh()->order_status);
        $this->assertSame('preparing', $ticket->fresh()->status);

        $service->transition($branch->id, $ticket->id, 'ready', $user);
        $this->assertSame('ready', $order->fresh()->order_status);
        $this->assertSame('ready', $ticket->fresh()->status);
        $this->assertSame(2, $ticket->statusHistory()->count());
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id, 'source' => 'kitchen', 'new_status' => 'ready',
        ]);
    }

    public function test_ticket_cannot_skip_directly_from_queued_to_ready(): void
    {
        [$branch, $order, $user] = $this->makeOrder();
        app(CreateKitchenTicketsAction::class)->execute($order);
        $ticket = KitchenTicket::query()->where('order_id', $order->id)->firstOrFail();

        $this->expectException(ValidationException::class);
        app(KitchenTicketService::class)->transition($branch->id, $ticket->id, 'ready', $user);
    }

    private function makeOrder(): array
    {
        $restaurant = Restaurant::query()->create([
            'name' => 'Workflow Test', 'slug' => 'workflow-'.Str::lower(Str::random(5)),
            'currency' => 'EGP', 'timezone' => 'Africa/Cairo', 'default_locale' => 'ar',
            'supported_locales' => ['ar', 'en'], 'is_active' => true,
        ]);
        $branch = Branch::query()->create([
            'restaurant_id' => $restaurant->id, 'code' => 'W'.Str::upper(Str::random(4)),
            'name' => 'Workflow Branch', 'slug' => 'workflow-branch-'.Str::lower(Str::random(5)),
            'is_primary' => true, 'is_active' => true,
        ]);
        $product = Product::query()->create([
            'restaurant_id' => $restaurant->id, 'name' => 'Test Meal',
            'slug' => 'test-meal-'.Str::lower(Str::random(5)), 'is_active' => true,
        ]);
        $user = User::query()->create([
            'name' => 'Kitchen Staff', 'email' => 'kitchen-'.Str::lower(Str::random(6)).'@example.com',
            'password' => 'StrongPassword!123', 'is_active' => true,
        ]);
        $order = Order::query()->create([
            'public_id' => (string) Str::uuid(), 'restaurant_id' => $restaurant->id, 'branch_id' => $branch->id,
            'order_number' => 'WF-'.Str::upper(Str::random(6)), 'idempotency_key' => (string) Str::uuid(),
            'order_type' => 'pickup', 'order_status' => 'pending_confirmation', 'payment_method' => 'cash',
            'payment_status' => 'pending', 'order_source' => 'website', 'currency' => 'EGP',
            'subtotal' => '100.00', 'discount_total' => '0.00', 'tax_total' => '0.00',
            'delivery_fee' => '0.00', 'total' => '100.00', 'customer_name' => 'Workflow Guest',
            'customer_phone' => '01000000000', 'placed_at' => now(),
        ]);
        $order->items()->create([
            'product_id' => $product->id, 'product_name_snapshot' => $product->name,
            'variant_name_snapshot' => 'Regular', 'quantity' => 1, 'unit_price' => '100.00', 'line_subtotal' => '100.00',
        ]);

        return [$branch, $order->fresh(), $user];
    }
}
