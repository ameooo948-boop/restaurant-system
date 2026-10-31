<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Branches\Models\Branch;
use App\Modules\Kitchen\Models\KitchenStation;
use App\Modules\Kitchen\Models\KitchenTicket;
use App\Modules\Kitchen\Models\KitchenTicketItem;
use App\Modules\Kitchen\Services\KitchenTicketService;
use App\Modules\Menu\Models\Product;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\OrderManagementService;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Staff\Models\BranchUserAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_list_is_scoped_to_the_current_branch(): void
    {
        [$branchA] = $this->makeBranch('List A');
        [$branchB] = $this->makeBranch('List B');
        $user = $this->staffFor($branchA, ['orders.view']);
        $orderA = $this->makeOrder($branchA, 'ORDER-A');
        $orderB = $this->makeOrder($branchB, 'ORDER-B');

        $this->actingAs($user)
            ->get('/admin/branches/'.$branchA->id.'/orders')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Orders/Index')
                ->where('orders.total', 1)
                ->where('orders.data.0.id', $orderA->id));

        $this->assertNotSame($orderA->id, $orderB->id);
    }

    public function test_branch_dashboard_metrics_are_scoped_to_the_current_branch(): void
    {
        [$branchA] = $this->makeBranch('Dashboard A');
        [$branchB] = $this->makeBranch('Dashboard B');
        $user = $this->staffFor($branchA, ['dashboard.view', 'orders.view']);
        $this->makeOrder($branchA, 'DASHBOARD-A');
        $this->makeOrder($branchB, 'DASHBOARD-B');

        $this->actingAs($user)
            ->get('/admin/branches/'.$branchA->id.'/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->where('stats.today_orders', 1)
                ->has('recentOrders', 1));
    }

    public function test_order_details_are_scoped_to_the_current_branch(): void
    {
        [$branchA] = $this->makeBranch('Details A');
        [$branchB] = $this->makeBranch('Details B');
        $user = $this->staffFor($branchA, ['orders.view']);
        $orderB = $this->makeOrder($branchB, 'ORDER-B-DETAIL');

        $this->actingAs($user)
            ->get('/admin/branches/'.$branchA->id.'/orders/'.$orderB->id)
            ->assertNotFound();
    }

    public function test_staff_can_confirm_an_order_and_history_is_recorded(): void
    {
        [$branch] = $this->makeBranch('Confirm');
        $user = $this->staffFor($branch, ['orders.view', 'orders.update_status', 'orders.confirm']);
        $order = $this->makeOrder($branch, 'ORDER-CONFIRM');

        $this->actingAs($user)
            ->from('/admin/branches/'.$branch->id.'/orders/'.$order->id)
            ->patch('/admin/branches/'.$branch->id.'/orders/'.$order->id.'/status', [
                'status' => 'confirmed', 'note' => 'تم التحقق من الطلب.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'order_status' => 'confirmed',
        ]);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'old_status' => 'pending_confirmation',
            'new_status' => 'confirmed',
            'source' => 'admin_operations',
            'changed_by' => $user->id,
        ]);
    }

    public function test_cancelling_an_unstarted_order_cancels_its_queued_kitchen_tickets(): void
    {
        [$branch] = $this->makeBranch('Cancel');
        $user = $this->staffFor($branch, ['orders.view', 'orders.update_status', 'orders.cancel']);
        $order = $this->makeOrder($branch, 'ORDER-CANCEL');
        [$ticket, $ticketItem] = $this->makeQueuedTicket($branch, $order);

        $this->actingAs($user)
            ->from('/admin/branches/'.$branch->id.'/orders/'.$order->id)
            ->patch('/admin/branches/'.$branch->id.'/orders/'.$order->id.'/status', [
                'status' => 'cancelled', 'note' => 'طلب العميل الإلغاء.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'order_status' => 'cancelled']);
        $this->assertDatabaseHas('kitchen_tickets', ['id' => $ticket->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('kitchen_ticket_items', ['id' => $ticketItem->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('kitchen_ticket_status_histories', [
            'kitchen_ticket_id' => $ticket->id,
            'old_status' => 'queued',
            'new_status' => 'cancelled',
            'source' => 'admin_order_cancellation',
        ]);
    }

    public function test_kitchen_cannot_start_a_ticket_after_its_order_was_cancelled(): void
    {
        [$branch] = $this->makeBranch('Cancelled Ticket');
        $order = $this->makeOrder($branch, 'ORDER-CANCELLED-TICKET');
        [$ticket] = $this->makeQueuedTicket($branch, $order);

        $actor = User::query()->create([
            'name' => 'Order Manager',
            'email' => 'cancel-manager-'.Str::lower(Str::random(6)).'@example.com',
            'password' => 'StrongPassword!123',
            'is_active' => true,
        ]);

        app(OrderManagementService::class)->transition($branch->id, $order->id, 'cancelled', $actor);

        $this->expectException(ValidationException::class);
        app(KitchenTicketService::class)->transition($branch->id, $ticket->id, 'preparing', $actor);
    }

    public function test_order_cannot_be_cancelled_after_kitchen_started_preparing(): void
    {
        [$branch] = $this->makeBranch('No Cancel');
        $user = $this->staffFor($branch, ['orders.view', 'orders.update_status', 'orders.cancel']);
        $order = $this->makeOrder($branch, 'ORDER-NO-CANCEL');
        [$ticket] = $this->makeQueuedTicket($branch, $order);
        $ticket->update(['status' => 'preparing', 'started_at' => now()]);

        $this->actingAs($user)
            ->from('/admin/branches/'.$branch->id.'/orders/'.$order->id)
            ->patch('/admin/branches/'.$branch->id.'/orders/'.$order->id.'/status', [
                'status' => 'cancelled',
            ])
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'order_status' => 'pending_confirmation']);
        $this->assertDatabaseHas('kitchen_tickets', ['id' => $ticket->id, 'status' => 'preparing']);
    }

    public function test_non_delivery_order_cannot_be_marked_out_for_delivery(): void
    {
        [$branch] = $this->makeBranch('Pickup');
        $user = $this->staffFor($branch, ['orders.view', 'orders.update_status']);
        $order = $this->makeOrder($branch, 'ORDER-PICKUP', 'pickup', 'ready');

        $this->actingAs($user)
            ->from('/admin/branches/'.$branch->id.'/orders/'.$order->id)
            ->patch('/admin/branches/'.$branch->id.'/orders/'.$order->id.'/status', [
                'status' => 'out_for_delivery',
            ])
            ->assertSessionHasErrors('status');
    }

    private function makeBranch(string $label): array
    {
        $restaurant = Restaurant::query()->create([
            'name' => $label,
            'slug' => Str::slug($label).'-'.Str::lower(Str::random(5)),
            'currency' => 'EGP',
            'timezone' => 'Africa/Cairo',
            'default_locale' => 'ar',
            'supported_locales' => ['ar', 'en'],
            'is_active' => true,
        ]);

        $branch = Branch::query()->create([
            'restaurant_id' => $restaurant->id,
            'code' => Str::upper(Str::random(5)),
            'name' => $label.' Branch',
            'slug' => Str::slug($label.'-branch').'-'.Str::lower(Str::random(5)),
            'is_primary' => true,
            'is_active' => true,
        ]);

        return [$branch, $restaurant];
    }

    private function staffFor(Branch $branch, array $permissionNames): User
    {
        $user = User::query()->create([
            'name' => 'Operations Staff',
            'email' => 'ops-'.Str::lower(Str::random(8)).'@example.com',
            'password' => 'StrongPassword!123',
            'is_active' => true,
        ]);

        BranchUserAssignment::query()->create([
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($branch->id);
        $permissions = collect($permissionNames)
            ->map(fn (string $name) => Permission::findOrCreate($name, 'web'));
        $role = Role::findOrCreate('test-operations-'.$branch->id, 'web');
        $role->syncPermissions($permissions);
        $user->assignRole($role);
        $registrar->setPermissionsTeamId(null);

        return $user;
    }

    private function makeOrder(Branch $branch, string $number, string $type = 'delivery', string $status = 'pending_confirmation'): Order
    {
        return Order::query()->create([
            'public_id' => (string) Str::uuid(),
            'restaurant_id' => $branch->restaurant_id,
            'branch_id' => $branch->id,
            'order_number' => $number,
            'idempotency_key' => (string) Str::uuid(),
            'order_type' => $type,
            'order_status' => $status,
            'payment_method' => 'cash',
            'payment_status' => 'pending',
            'order_source' => 'website',
            'currency' => 'EGP',
            'subtotal' => '100.00',
            'discount_total' => '0.00',
            'tax_total' => '0.00',
            'delivery_fee' => $type === 'delivery' ? '15.00' : '0.00',
            'total' => $type === 'delivery' ? '115.00' : '100.00',
            'customer_name' => 'Test Customer',
            'customer_phone' => '01000000000',
            'placed_at' => now(),
        ]);
    }

    private function makeQueuedTicket(Branch $branch, Order $order): array
    {
        $station = KitchenStation::query()->create([
            'branch_id' => $branch->id,
            'code' => 'GENERAL',
            'name' => 'General Kitchen',
            'is_default' => true,
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'restaurant_id' => $branch->restaurant_id,
            'name' => 'Test Product',
            'slug' => 'test-product-'.Str::lower(Str::random(6)),
            'is_active' => true,
        ]);

        $orderItem = $order->items()->create([
            'product_id' => $product->id,
            'product_name_snapshot' => 'Test Product',
            'variant_name_snapshot' => 'Regular',
            'quantity' => 1,
            'unit_price' => '100.00',
            'line_subtotal' => '100.00',
        ]);

        $ticket = KitchenTicket::query()->create([
            'public_id' => (string) Str::uuid(),
            'branch_id' => $branch->id,
            'order_id' => $order->id,
            'kitchen_station_id' => $station->id,
            'ticket_number' => 'TICKET-'.Str::upper(Str::random(7)),
            'status' => 'queued',
            'queued_at' => now(),
        ]);

        $ticketItem = KitchenTicketItem::query()->create([
            'kitchen_ticket_id' => $ticket->id,
            'order_item_id' => $orderItem->id,
            'status' => 'queued',
            'product_name_snapshot' => 'Test Product',
            'variant_name_snapshot' => 'Regular',
            'quantity' => 1,
        ]);

        return [$ticket, $ticketItem];
    }
}
