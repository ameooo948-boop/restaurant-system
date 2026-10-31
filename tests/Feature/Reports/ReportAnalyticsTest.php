<?php

namespace Tests\Feature\Reports;

use App\Models\User;
use App\Modules\Branches\Models\Branch;
use App\Modules\Orders\Models\Order;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Staff\Models\BranchUserAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ReportAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_is_branch_scoped_and_excludes_cancelled_orders_from_sales(): void
    {
        [$branchA] = $this->makeBranch('Report A');
        [$branchB] = $this->makeBranch('Report B');
        $user = $this->staffFor($branchA, ['reports.view']);

        $completed = $this->makeOrder($branchA, 'REPORT-A-1', 'completed', 120.00);
        $this->makeOrder($branchA, 'REPORT-A-CANCEL', 'cancelled', 80.00);
        $this->makeOrder($branchB, 'REPORT-B-1', 'completed', 900.00);

        $completed->payments()->create([
            'method' => 'cash', 'provider' => 'cash', 'status' => 'succeeded',
            'amount' => '120.00', 'currency' => 'EGP', 'idempotency_key' => (string) Str::uuid(), 'paid_at' => now(),
        ]);
        $completed->update(['payment_status' => 'succeeded']);

        $this->actingAs($user)
            ->get('/admin/branches/'.$branchA->id.'/reports?from='.now()->toDateString().'&to='.now()->toDateString())
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Reports/Index')
                ->where('report.summary.fulfilled_orders', 1)
                ->where('report.summary.net_sales', 120)
                ->where('report.summary.cancelled_orders', 1)
                ->where('report.summary.collected_payments', 120));
    }

    public function test_user_without_report_permission_is_forbidden(): void
    {
        [$branch] = $this->makeBranch('No Reports');
        $user = $this->staffFor($branch, ['dashboard.view']);

        $this->actingAs($user)->get('/admin/branches/'.$branch->id.'/reports')->assertForbidden();
    }

    private function makeBranch(string $label): array
    {
        $restaurant = Restaurant::query()->create([
            'name' => $label, 'slug' => Str::slug($label).'-'.Str::lower(Str::random(5)),
            'currency' => 'EGP', 'timezone' => 'Africa/Cairo', 'default_locale' => 'ar',
            'supported_locales' => ['ar', 'en'], 'is_active' => true,
        ]);
        $branch = Branch::query()->create([
            'restaurant_id' => $restaurant->id, 'code' => Str::upper(Str::random(5)),
            'name' => $label.' Branch', 'slug' => Str::slug($label.'-branch').'-'.Str::lower(Str::random(5)),
            'is_primary' => true, 'is_active' => true,
        ]);
        return [$branch, $restaurant];
    }

    private function staffFor(Branch $branch, array $permissionNames): User
    {
        $user = User::query()->create([
            'name' => 'Report Staff', 'email' => 'report-'.Str::lower(Str::random(8)).'@example.com',
            'password' => 'StrongPassword!123', 'is_active' => true,
        ]);
        BranchUserAssignment::query()->create(['branch_id' => $branch->id, 'user_id' => $user->id, 'is_active' => true]);
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($branch->id);
        $permissions = collect($permissionNames)->map(fn (string $name) => Permission::findOrCreate($name, 'web'));
        $role = Role::findOrCreate('test-reports-'.$branch->id, 'web');
        $role->syncPermissions($permissions);
        $user->assignRole($role);
        $registrar->setPermissionsTeamId(null);
        return $user;
    }

    private function makeOrder(Branch $branch, string $number, string $status, float $amount): Order
    {
        return Order::query()->create([
            'public_id' => (string) Str::uuid(), 'restaurant_id' => $branch->restaurant_id,
            'branch_id' => $branch->id, 'order_number' => $number, 'idempotency_key' => (string) Str::uuid(),
            'order_type' => 'pickup', 'order_status' => $status, 'payment_method' => 'cash',
            'payment_status' => 'pending', 'order_source' => 'website', 'currency' => 'EGP',
            'subtotal' => number_format($amount, 2, '.', ''), 'discount_total' => '0.00',
            'tax_total' => '0.00', 'delivery_fee' => '0.00', 'total' => number_format($amount, 2, '.', ''),
            'customer_name' => 'Report Customer', 'customer_phone' => '01000000000', 'placed_at' => now(),
        ]);
    }
}
