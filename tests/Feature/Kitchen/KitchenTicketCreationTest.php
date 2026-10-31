<?php

namespace Tests\Feature\Kitchen;

use App\Modules\Branches\Models\Branch;
use App\Modules\Kitchen\Actions\CreateKitchenTicketsAction;
use App\Modules\Kitchen\Models\KitchenStation;
use App\Modules\Kitchen\Models\KitchenStationProduct;
use App\Modules\Kitchen\Models\KitchenTicket;
use App\Modules\Menu\Models\Product;
use App\Modules\Orders\Models\Order;
use App\Modules\Restaurants\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class KitchenTicketCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_items_are_routed_to_their_station_or_general_fallback(): void
    {
        [$branch, $order, $grillProduct, $unassignedProduct] = $this->makeOrderWithProducts();
        $grill = KitchenStation::query()->create([
            'branch_id' => $branch->id, 'code' => 'GRILL', 'name' => 'Grill', 'is_active' => true,
        ]);
        KitchenStationProduct::query()->create([
            'branch_id' => $branch->id, 'kitchen_station_id' => $grill->id, 'product_id' => $grillProduct->id,
        ]);

        app(CreateKitchenTicketsAction::class)->execute($order);

        $this->assertDatabaseHas('kitchen_tickets', [
            'order_id' => $order->id,
            'kitchen_station_id' => $grill->id,
            'status' => 'queued',
        ]);
        $general = KitchenStation::query()->where('branch_id', $branch->id)->where('code', 'GENERAL')->firstOrFail();
        $this->assertDatabaseHas('kitchen_tickets', [
            'order_id' => $order->id,
            'kitchen_station_id' => $general->id,
            'status' => 'queued',
        ]);
        $this->assertSame(2, KitchenTicket::query()->where('order_id', $order->id)->count());
        $fallbackItem = KitchenTicket::query()
            ->where('order_id', $order->id)
            ->where('kitchen_station_id', $general->id)
            ->firstOrFail()
            ->items()
            ->firstOrFail();
        $this->assertSame($unassignedProduct->name, $fallbackItem->product_name_snapshot);
    }

    public function test_ticket_generation_is_idempotent(): void
    {
        [$branch, $order] = $this->makeOrderWithProducts();
        $action = app(CreateKitchenTicketsAction::class);

        $action->execute($order);
        $action->execute($order);

        $ticket = KitchenTicket::query()->where('order_id', $order->id)->firstOrFail();
        $this->assertSame(1, KitchenTicket::query()->where('order_id', $order->id)->count());
        $this->assertSame(2, $ticket->items()->count());
    }

    private function makeOrderWithProducts(): array
    {
        $restaurant = Restaurant::query()->create([
            'name' => 'Kitchen Test', 'slug' => 'kitchen-test-'.Str::lower(Str::random(5)),
            'currency' => 'EGP', 'timezone' => 'Africa/Cairo', 'default_locale' => 'ar',
            'supported_locales' => ['ar', 'en'], 'is_active' => true,
        ]);
        $branch = Branch::query()->create([
            'restaurant_id' => $restaurant->id, 'code' => 'K'.Str::upper(Str::random(4)),
            'name' => 'Kitchen Branch', 'slug' => 'kitchen-'.Str::lower(Str::random(5)),
            'is_primary' => true, 'is_active' => true,
        ]);

        $products = collect(['Grill Burger', 'Lemon Juice'])->map(function (string $name) use ($restaurant): Product {
            return Product::query()->create([
                'restaurant_id' => $restaurant->id,
                'name' => $name,
                'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
                'is_active' => true,
            ]);
        });

        $order = Order::query()->create([
            'public_id' => (string) Str::uuid(), 'restaurant_id' => $restaurant->id, 'branch_id' => $branch->id,
            'order_number' => 'KITCHEN-'.Str::upper(Str::random(6)), 'idempotency_key' => (string) Str::uuid(),
            'order_type' => 'pickup', 'order_status' => 'pending_confirmation', 'payment_method' => 'cash',
            'payment_status' => 'pending', 'order_source' => 'website', 'currency' => 'EGP',
            'subtotal' => '200.00', 'discount_total' => '0.00', 'tax_total' => '0.00',
            'delivery_fee' => '0.00', 'total' => '200.00', 'customer_name' => 'Kitchen Guest',
            'customer_phone' => '01000000000', 'placed_at' => now(),
        ]);

        foreach ($products as $index => $product) {
            $order->items()->create([
                'product_id' => $product->id,
                'product_name_snapshot' => $product->name,
                'variant_name_snapshot' => 'Regular',
                'quantity' => $index + 1,
                'unit_price' => '100.00',
                'line_subtotal' => (string) (100 * ($index + 1)).'.00',
            ]);
        }

        return [$branch, $order->fresh(), $products[0], $products[1]];
    }
}
