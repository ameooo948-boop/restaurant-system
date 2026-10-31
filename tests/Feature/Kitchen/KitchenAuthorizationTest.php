<?php

namespace Tests\Feature\Kitchen;

use App\Models\User;
use App\Modules\Branches\Models\Branch;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Staff\Models\BranchUserAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class KitchenAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_kitchen_staff_needs_a_branch_assignment_and_kitchen_permission(): void
    {
        [$restaurant, $branch] = $this->makeBranch();
        $user = User::query()->create([
            'name' => 'Kitchen Operator', 'email' => 'operator@example.com',
            'password' => 'StrongPassword!123', 'is_active' => true,
        ]);

        $this->actingAs($user)->get('/admin/branches/'.$branch->id.'/kitchen')->assertForbidden();

        BranchUserAssignment::query()->create([
            'branch_id' => $branch->id, 'user_id' => $user->id, 'is_active' => true,
        ]);
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($branch->id);
        $permission = Permission::findOrCreate('kitchen.view', 'web');
        $role = Role::findOrCreate('kitchen_staff', 'web');
        $role->syncPermissions([$permission]);
        $user->assignRole($role);
        $registrar->setPermissionsTeamId(null);

        $this->actingAs($user)->get('/admin/branches/'.$branch->id.'/kitchen')->assertOk();
    }

    private function makeBranch(): array
    {
        $restaurant = Restaurant::query()->create([
            'name' => 'Kitchen Auth', 'slug' => 'kitchen-auth-'.Str::lower(Str::random(5)),
            'currency' => 'EGP', 'timezone' => 'Africa/Cairo', 'default_locale' => 'ar',
            'supported_locales' => ['ar', 'en'], 'is_active' => true,
        ]);
        $branch = Branch::query()->create([
            'restaurant_id' => $restaurant->id, 'code' => 'A'.Str::upper(Str::random(4)),
            'name' => 'Auth Branch', 'slug' => 'auth-branch-'.Str::lower(Str::random(5)),
            'is_primary' => true, 'is_active' => true,
        ]);

        return [$restaurant, $branch];
    }
}
