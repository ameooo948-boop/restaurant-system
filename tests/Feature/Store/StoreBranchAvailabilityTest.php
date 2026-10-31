<?php

namespace Tests\Feature\Store;

use App\Modules\Branches\Models\Branch;
use App\Modules\Restaurants\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StoreBranchAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private function makeBranch(bool $branchActive = true, bool $restaurantActive = true): Branch
    {
        $restaurant = Restaurant::create([
            'name' => 'Availability Test Restaurant',
            'slug' => 'availability-'.Str::lower(Str::random(10)),
            'currency' => 'EGP',
            'timezone' => 'Africa/Cairo',
            'default_locale' => 'ar',
            'supported_locales' => ['ar', 'en'],
            'is_active' => $restaurantActive,
        ]);

        return Branch::create([
            'restaurant_id' => $restaurant->id,
            'code' => 'T'.Str::upper(Str::random(5)),
            'name' => 'Availability Test Branch',
            'slug' => 'availability-branch-'.Str::lower(Str::random(8)),
            'is_primary' => true,
            'is_active' => $branchActive,
        ]);
    }

    public function test_active_branch_can_resolve_store_menu(): void
    {
        $branch = $this->makeBranch();

        $this->get('/branches/'.$branch->id.'/menu')->assertOk();
    }

    public function test_inactive_branch_is_not_available_on_storefront(): void
    {
        $branch = $this->makeBranch(branchActive: false);

        $this->get('/branches/'.$branch->id.'/menu')->assertNotFound();
    }

    public function test_branch_with_inactive_restaurant_is_not_available_on_storefront(): void
    {
        $branch = $this->makeBranch(restaurantActive: false);

        $this->get('/branches/'.$branch->id.'/menu')->assertNotFound();
    }
}
