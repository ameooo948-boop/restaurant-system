<?php

namespace Tests\Feature\Store;

use App\Modules\Branches\Models\Branch;
use App\Modules\Branches\Models\BranchSetting;
use App\Modules\Cart\Models\Cart;
use App\Modules\Menu\Models\MenuCategory;
use App\Modules\Menu\Models\ModifierGroup;
use App\Modules\Menu\Models\ModifierOption;
use App\Modules\Menu\Models\Product;
use App\Modules\Menu\Models\ProductVariant;
use App\Modules\Orders\Models\Order;
use App\Modules\Restaurants\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StoreCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function makeBranch(string $label = 'Main'): Branch
    {
        $restaurant = Restaurant::create([
            'name' => 'Test Restaurant',
            'slug' => 'test-restaurant-'.Str::lower(Str::random(6)),
            'currency' => 'EGP',
            'timezone' => 'Africa/Cairo',
            'default_locale' => 'ar',
            'supported_locales' => ['ar', 'en'],
            'is_active' => true,
        ]);

        return Branch::create([
            'restaurant_id' => $restaurant->id,
            'code' => strtoupper(substr($label, 0, 4)).Str::upper(Str::random(2)),
            'name' => $label,
            'slug' => Str::slug($label).'-'.Str::lower(Str::random(4)),
            'is_primary' => true,
            'is_active' => true,
        ]);
    }

    private function makeProduct(Branch $branch, bool $withModifier = true): array
    {
        $category = MenuCategory::create([
            'restaurant_id' => $branch->restaurant_id,
            'name' => 'Main Meals',
            'slug' => 'main-meals-'.Str::lower(Str::random(5)),
            'is_active' => true,
        ]);
        $product = Product::create([
            'restaurant_id' => $branch->restaurant_id,
            'category_id' => $category->id,
            'name' => 'Test Burger',
            'slug' => 'test-burger-'.Str::lower(Str::random(5)),
            'is_active' => true,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Regular',
            'base_price' => '100.00',
            'is_default' => true,
            'is_active' => true,
        ]);
        $variant->branchSettings()->create([
            'branch_id' => $branch->id,
            'price_override' => '120.00',
            'is_available' => true,
        ]);

        $option = null;
        if ($withModifier) {
            $group = ModifierGroup::create([
                'restaurant_id' => $branch->restaurant_id,
                'name' => 'Extras',
                'selection_type' => 'multiple',
                'min_selections' => 0,
                'max_selections' => 2,
                'is_active' => true,
            ]);
            $option = ModifierOption::create([
                'modifier_group_id' => $group->id,
                'name' => 'Extra Cheese',
                'price_delta' => '20.00',
                'is_active' => true,
            ]);
            $product->modifierGroups()->attach($group->id);
        }

        return [$product, $variant, $option];
    }

    private function addToGuestCart(Branch $branch, ProductVariant $variant, ?ModifierOption $option = null): Cart
    {
        $this->post('/branches/'.$branch->id.'/cart/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
            // Deliberately forged client price: Checkout must ignore it.
            'unit_price' => '0.01',
            'modifiers' => $option ? [[
                'modifier_option_id' => $option->id,
                'quantity' => 1,
            ]] : [],
        ])->assertRedirect();

        return Cart::query()->where('branch_id', $branch->id)->where('status', 'active')->firstOrFail();
    }

    private function checkoutPayload(Cart $cart, string $orderType = 'delivery', string $paymentMethod = 'cash'): array
    {
        return [
            'cart_public_id' => $cart->public_id,
            'idempotency_key' => (string) Str::uuid(),
            'order_type' => $orderType,
            'payment_method' => $paymentMethod,
            'customer_name' => 'Test Customer',
            'customer_phone' => '01000000000',
            'customer_email' => 'buyer@example.com',
            'address_line' => $orderType === 'delivery' ? 'Street 1' : null,
            'city' => $orderType === 'delivery' ? 'Mansoura' : null,
        ];
    }

    public function test_guest_can_checkout_using_branch_prices_and_product_snapshots(): void
    {
        $branch = $this->makeBranch();
        [$product, $variant, $option] = $this->makeProduct($branch);
        BranchSetting::create([
            'branch_id' => $branch->id,
            'setting_key' => 'delivery_fee',
            'setting_value' => ['amount' => '15.00'],
        ]);
        $cart = $this->addToGuestCart($branch, $variant, $option);
        $payload = $this->checkoutPayload($cart);

        $response = $this->post('/branches/'.$branch->id.'/checkout', $payload);
        $response->assertRedirect();

        $order = Order::query()->with(['items.modifiers', 'payments'])->firstOrFail();
        $this->assertSame('295.00', $order->total);
        $this->assertSame('15.00', $order->delivery_fee);
        $this->assertSame('Test Burger', $order->items->first()->product_name_snapshot);
        $this->assertSame('Extra Cheese', $order->items->first()->modifiers->first()->option_name_snapshot);
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame('pending', $order->payments->first()->status);
        $this->assertSame('converted', $cart->fresh()->status);
    }

    public function test_checkout_is_idempotent_for_the_same_converted_cart(): void
    {
        $branch = $this->makeBranch();
        [, $variant] = $this->makeProduct($branch, false);
        $cart = $this->addToGuestCart($branch, $variant);
        $payload = $this->checkoutPayload($cart, 'pickup');

        $this->post('/branches/'.$branch->id.'/checkout', $payload)->assertRedirect();
        $firstOrder = Order::query()->where('cart_id', $cart->id)->firstOrFail();
        $this->post('/branches/'.$branch->id.'/checkout', $payload)->assertRedirect();

        $this->assertSame(1, Order::query()->where('cart_id', $cart->id)->count());
        $this->assertSame($firstOrder->id, Order::query()->where('cart_id', $cart->id)->value('id'));
    }

    public function test_checkout_rejects_vodafone_cash_until_gateway_is_enabled(): void
    {
        $branch = $this->makeBranch();
        [, $variant] = $this->makeProduct($branch, false);
        $cart = $this->addToGuestCart($branch, $variant);

        $this->post('/branches/'.$branch->id.'/checkout', $this->checkoutPayload($cart, 'pickup', 'vodafone_cash'))
            ->assertSessionHasErrors('payment_method');

        $this->assertSame(0, Order::query()->count());
    }

    public function test_delivery_requires_address_and_city(): void
    {
        $branch = $this->makeBranch();
        [, $variant] = $this->makeProduct($branch, false);
        $cart = $this->addToGuestCart($branch, $variant);
        $payload = $this->checkoutPayload($cart);
        unset($payload['address_line'], $payload['city']);

        $this->post('/branches/'.$branch->id.'/checkout', $payload)
            ->assertSessionHasErrors(['address_line', 'city']);

        $this->assertSame(0, Order::query()->count());
    }

    public function test_cart_rejects_variant_from_another_restaurant(): void
    {
        $branchA = $this->makeBranch('Alpha');
        $branchB = $this->makeBranch('Beta');
        [, $variantFromOtherRestaurant] = $this->makeProduct($branchB, false);

        $this->post('/branches/'.$branchA->id.'/cart/items', [
            'product_variant_id' => $variantFromOtherRestaurant->id,
            'quantity' => 1,
            'modifiers' => [],
        ])->assertSessionHasErrors('product_variant_id');

        $this->assertSame(0, Cart::query()->count());
    }

    public function test_guest_cannot_view_another_guest_order(): void
    {
        $branch = $this->makeBranch();
        [, $variant] = $this->makeProduct($branch, false);
        $cart = $this->addToGuestCart($branch, $variant);
        $this->post('/branches/'.$branch->id.'/checkout', $this->checkoutPayload($cart, 'pickup'))->assertRedirect();
        $order = Order::query()->firstOrFail();

        $this->flushSession();
        $this->get('/orders/'.$order->public_id)->assertNotFound();
    }
}
