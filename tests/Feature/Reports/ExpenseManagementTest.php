<?php

namespace Tests\Feature\Reports;

use App\Models\User;
use App\Modules\Branches\Models\Branch;
use App\Modules\Reports\Models\ExpenseCategory;
use App\Modules\Restaurants\Models\Restaurant;
use App\Modules\Staff\Models\BranchUserAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ExpenseManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_record_and_void_branch_expense_without_deleting_history(): void
    {
        [$branch] = $this->makeBranch('Expense Branch');
        $user = $this->staffFor($branch, ['expenses.manage']);
        $category = ExpenseCategory::query()->create(['restaurant_id' => $branch->restaurant_id, 'name' => 'Utilities', 'is_active' => true]);

        $this->actingAs($user)->post('/admin/branches/'.$branch->id.'/expenses', [
            'expense_category_id' => $category->id, 'amount' => '350.00',
            'expense_date' => now()->toDateString(), 'payment_method' => 'cash',
            'description' => 'Monthly electricity bill',
        ])->assertRedirect();

        $this->assertDatabaseHas('expenses', ['branch_id' => $branch->id, 'amount' => '350.00', 'status' => 'posted']);
        $expenseId = (int) DB::table('expenses')->where('branch_id', $branch->id)->value('id');

        $this->actingAs($user)->patch('/admin/branches/'.$branch->id.'/expenses/'.$expenseId.'/void', [
            'reason' => 'تم إدخال المصروف مرتين بالخطأ.',
        ])->assertRedirect();

        $this->assertDatabaseHas('expenses', [
            'id' => $expenseId, 'status' => 'voided', 'voided_by' => $user->id,
            'void_reason' => 'تم إدخال المصروف مرتين بالخطأ.',
        ]);
    }

    public function test_expense_category_from_another_restaurant_is_rejected(): void
    {
        [$branchA] = $this->makeBranch('Expense A');
        [, $restaurantB] = $this->makeBranch('Expense B');
        $user = $this->staffFor($branchA, ['expenses.manage']);
        $foreignCategory = ExpenseCategory::query()->create(['restaurant_id' => $restaurantB->id, 'name' => 'Foreign', 'is_active' => true]);

        $this->actingAs($user)->from('/admin/branches/'.$branchA->id.'/expenses')->post('/admin/branches/'.$branchA->id.'/expenses', [
            'expense_category_id' => $foreignCategory->id, 'amount' => '10.00',
            'expense_date' => now()->toDateString(), 'payment_method' => 'cash',
            'description' => 'Invalid foreign category expense',
        ])->assertSessionHasErrors('expense_category_id');

        $this->assertDatabaseCount('expenses', 0);
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
            'name' => 'Expense Staff', 'email' => 'expense-'.Str::lower(Str::random(8)).'@example.com',
            'password' => 'StrongPassword!123', 'is_active' => true,
        ]);
        BranchUserAssignment::query()->create(['branch_id' => $branch->id, 'user_id' => $user->id, 'is_active' => true]);
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($branch->id);
        $permissions = collect($permissionNames)->map(fn (string $name) => Permission::findOrCreate($name, 'web'));
        $role = Role::findOrCreate('test-expenses-'.$branch->id, 'web');
        $role->syncPermissions($permissions);
        $user->assignRole($role);
        $registrar->setPermissionsTeamId(null);
        return $user;
    }
}
