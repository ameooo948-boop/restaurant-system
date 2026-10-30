<?php

namespace Tests\Feature\Menu;

use App\Models\User;
use App\Modules\Branches\Models\Branch;
use App\Modules\Menu\Models\MenuCategory;
use App\Modules\Menu\Models\ModifierGroup;
use App\Modules\Menu\Models\Product;
use App\Modules\Menu\Models\ProductVariant;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Staff\Models\BranchUserAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MenuManagementTest extends TestCase
{
    use RefreshDatabase;

    private int $restaurantSequence = 0;

    private function makeBranch(string $name): Branch
    {
        $this->restaurantSequence++;
        $restaurant = Restaurant::create([
            'name' => 'Restaurant '.$this->restaurantSequence,
            'slug' => 'restaurant-'.$this->restaurantSequence,
            'currency' => 'EGP',
            'timezone' => 'Africa/Cairo',
            'default_locale' => 'ar',
            'supported_locales' => ['ar', 'en'],
            'is_active' => true,
        ]);

        return Branch::create([
            'restaurant_id' => $restaurant->id,
            'code' => 'BR'.$this->restaurantSequence,
            'name' => $name,
            'slug' => 'branch-'.$this->restaurantSequence,
            'is_primary' => true,
            'is_active' => true,
        ]);
    }

    private function staffFor(Branch $branch, array $permissions = ['menu.view', 'menu.catalog.manage']): User
    {
        $user = User::create([
            'name' => 'Menu Manager',
            'email' => 'manager-'.$branch->id.'@example.com',
            'password' => 'StrongPassword!123',
            'is_active' => true,
        ]);

        BranchUserAssignment::create([
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($branch->id);

        $permissionModels = collect($permissions)
            ->map(fn (string $permission) => Permission::findOrCreate($permission, 'web'));

        $role = Role::findOrCreate('menu_manager', 'web');
        $role->syncPermissions($permissionModels);
        $user->assignRole($role);

        $registrar->setPermissionsTeamId(null);
        $registrar->forgetCachedPermissions();

        return $user;
    }

    public function test_staff_with_menu_permissions_can_create_a_category(): void
    {
        $branch = $this->makeBranch('Main Branch');
        $user = $this->staffFor($branch);

        $this->actingAs($user)
            ->post('/admin/branches/'.$branch->id.'/menu/categories', [
                'name' => 'وجبات رئيسية',
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('menu_categories', [
            'restaurant_id' => $branch->restaurant_id,
            'name' => 'وجبات رئيسية',
            'is_active' => true,
        ]);
    }

    public function test_category_from_another_restaurant_cannot_be_assigned_to_product(): void
    {
        $branchA = $this->makeBranch('Branch A');
        $branchB = $this->makeBranch('Branch B');
        $foreignCategory = MenuCategory::create([
            'restaurant_id' => $branchB->restaurant_id,
            'name' => 'Foreign Category',
            'slug' => 'foreign-category',
            'is_active' => true,
        ]);
        $user = $this->staffFor($branchA);

        $this->actingAs($user)
            ->post('/admin/branches/'.$branchA->id.'/menu/products', [
                'category_id' => $foreignCategory->id,
                'name' => 'Test Burger',
                'variants' => [[
                    'name' => 'Regular',
                    'base_price' => '100.00',
                    'is_default' => true,
                    'is_active' => true,
                ]],
            ])
            ->assertSessionHasErrors('category_id');

        $this->assertDatabaseMissing('products', [
            'restaurant_id' => $branchA->restaurant_id,
            'name' => 'Test Burger',
        ]);
    }

    public function test_product_saves_branch_specific_price_and_availability(): void
    {
        $branch = $this->makeBranch('Product Branch');
        $category = MenuCategory::create([
            'restaurant_id' => $branch->restaurant_id,
            'name' => 'Burgers',
            'slug' => 'burgers-'.$branch->id,
            'is_active' => true,
        ]);
        $modifierGroup = ModifierGroup::create([
            'restaurant_id' => $branch->restaurant_id,
            'name' => 'Extra Cheese',
            'selection_type' => 'single',
            'min_selections' => 0,
            'max_selections' => 1,
            'is_active' => true,
        ]);
        $user = $this->staffFor($branch);

        $this->actingAs($user)
            ->post('/admin/branches/'.$branch->id.'/menu/products', [
                'category_id' => $category->id,
                'name' => 'Classic Burger',
                'is_active' => true,
                'modifier_group_ids' => [$modifierGroup->id],
                'variants' => [[
                    'name' => 'Regular',
                    'sku' => 'CB-'.$branch->id,
                    'base_price' => '150.00',
                    'branch_price' => '160.00',
                    'branch_is_available' => false,
                    'is_default' => true,
                    'is_active' => true,
                ]],
            ])
            ->assertRedirect();

        $product = Product::query()->where('restaurant_id', $branch->restaurant_id)->where('name', 'Classic Burger')->firstOrFail();
        $variant = ProductVariant::query()->where('product_id', $product->id)->firstOrFail();

        $this->assertDatabaseHas('branch_product_settings', [
            'branch_id' => $branch->id,
            'product_variant_id' => $variant->id,
            'price_override' => '160.00',
            'is_available' => false,
        ]);
        $this->assertDatabaseHas('product_modifier_groups', [
            'product_id' => $product->id,
            'modifier_group_id' => $modifierGroup->id,
        ]);
    }

    public function test_menu_permissions_are_required_even_for_assigned_staff(): void
    {
        $branch = $this->makeBranch('Read Only Branch');
        $user = $this->staffFor($branch, []);

        $this->actingAs($user)
            ->get('/admin/branches/'.$branch->id.'/menu/categories')
            ->assertForbidden();
    }

    public function test_staff_assigned_to_another_branch_cannot_access_menu(): void
    {
        $assignedBranch = $this->makeBranch('Assigned Branch');
        $otherBranch = $this->makeBranch('Other Branch');
        $user = $this->staffFor($assignedBranch);

        $this->actingAs($user)
            ->get('/admin/branches/'.$otherBranch->id.'/menu/categories')
            ->assertForbidden();
    }

    public function test_modifier_group_enforces_valid_selection_limits(): void
    {
        $branch = $this->makeBranch('Modifier Branch');
        $user = $this->staffFor($branch);

        $this->actingAs($user)
            ->post('/admin/branches/'.$branch->id.'/menu/modifiers', [
                'name' => 'Choose Sauce',
                'selection_type' => 'single',
                'min_selections' => 2,
                'max_selections' => 2,
                'options' => [[
                    'name' => 'BBQ',
                    'price_delta' => '0.00',
                ]],
            ])
            ->assertSessionHasErrors('min_selections');

        $this->assertDatabaseMissing('modifier_groups', [
            'restaurant_id' => $branch->restaurant_id,
            'name' => 'Choose Sauce',
        ]);
    }
}
