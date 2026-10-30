<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use App\Modules\Branches\Models\Branch;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Staff\Models\BranchUserAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BranchAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function makeBranch(string $name): Branch
    {
        $restaurant = Restaurant::create([
            'name' => 'Test Restaurant',
            'slug' => 'test-restaurant',
            'currency' => 'EGP',
            'timezone' => 'Africa/Cairo',
            'default_locale' => 'ar',
            'supported_locales' => ['ar', 'en'],
            'is_active' => true,
        ]);

        return Branch::create([
            'restaurant_id' => $restaurant->id,
            'code' => strtoupper(substr($name, 0, 4)),
            'name' => $name,
            'slug' => str()->slug($name),
            'is_primary' => true,
            'is_active' => true,
        ]);
    }

    public function test_user_without_branch_assignment_cannot_open_branch_dashboard(): void
    {
        $branch = $this->makeBranch('Branch Alpha');
        $user = User::create([
            'name' => 'Customer',
            'email' => 'customer@example.com',
            'password' => 'StrongPassword!123',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get('/admin/branches/'.$branch->id.'/dashboard')
            ->assertForbidden();
    }

    public function test_assigned_staff_without_dashboard_permission_is_forbidden(): void
    {
        $branch = $this->makeBranch('Branch Beta');
        $user = User::create([
            'name' => 'Staff',
            'email' => 'staff@example.com',
            'password' => 'StrongPassword!123',
            'is_active' => true,
        ]);
        BranchUserAssignment::create([
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get('/admin/branches/'.$branch->id.'/dashboard')
            ->assertForbidden();
    }

    public function test_assigned_branch_owner_can_open_only_their_branch_dashboard(): void
    {
        $restaurant = Restaurant::create([
            'name' => 'Test Restaurant', 'slug' => 'test-restaurant', 'currency' => 'EGP',
            'timezone' => 'Africa/Cairo', 'default_locale' => 'ar',
            'supported_locales' => ['ar', 'en'], 'is_active' => true,
        ]);
        $branchA = Branch::create([
            'restaurant_id' => $restaurant->id, 'code' => 'A001', 'name' => 'Branch A',
            'slug' => 'branch-a', 'is_primary' => true, 'is_active' => true,
        ]);
        $branchB = Branch::create([
            'restaurant_id' => $restaurant->id, 'code' => 'B001', 'name' => 'Branch B',
            'slug' => 'branch-b', 'is_primary' => false, 'is_active' => true,
        ]);
        $user = User::create([
            'name' => 'Owner', 'email' => 'owner@example.com',
            'password' => 'StrongPassword!123', 'is_active' => true,
        ]);
        BranchUserAssignment::create([
            'branch_id' => $branchA->id, 'user_id' => $user->id, 'is_active' => true,
        ]);

        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($branchA->id);
        $permission = Permission::findOrCreate('dashboard.view', 'web');
        $role = Role::findOrCreate('restaurant_owner', 'web');
        $role->givePermissionTo($permission);
        $user->assignRole($role);
        $registrar->setPermissionsTeamId(null);

        $this->actingAs($user)
            ->get('/admin/branches/'.$branchA->id.'/dashboard')
            ->assertOk();

        $this->get('/admin/branches/'.$branchB->id.'/dashboard')
            ->assertForbidden();
    }
}
