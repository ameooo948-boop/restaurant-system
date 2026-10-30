<?php

namespace Tests\Feature\Foundation;

use App\Modules\Branches\Models\Branch;
use App\Modules\Restaurants\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantBranchSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_restaurant_can_have_multiple_branches(): void
    {
        $restaurant = Restaurant::query()->create([
            'name' => 'Test Restaurant',
            'slug' => 'test-restaurant',
            'currency' => 'EGP',
            'timezone' => 'Africa/Cairo',
            'default_locale' => 'ar',
            'supported_locales' => ['ar', 'en'],
            'is_active' => true,
        ]);

        $mainBranch = $restaurant->branches()->create([
            'code' => 'MAIN',
            'name' => 'Main Branch',
            'slug' => 'main',
            'is_primary' => true,
            'is_active' => true,
        ]);

        $secondBranch = $restaurant->branches()->create([
            'code' => 'BR-02',
            'name' => 'Second Branch',
            'slug' => 'second',
            'is_primary' => false,
            'is_active' => true,
        ]);

        $this->assertSame(2, $restaurant->branches()->count());
        $this->assertTrue($mainBranch->fresh()->is_primary);
        $this->assertFalse($secondBranch->fresh()->is_primary);
        $this->assertSame($restaurant->id, $secondBranch->restaurant->id);
    }

    public function test_branch_business_hours_are_related_to_the_branch(): void
    {
        $restaurant = Restaurant::query()->create([
            'name' => 'Hours Test Restaurant',
            'slug' => 'hours-test-restaurant',
            'currency' => 'EGP',
            'timezone' => 'Africa/Cairo',
            'default_locale' => 'ar',
            'supported_locales' => ['ar', 'en'],
            'is_active' => true,
        ]);

        $branch = Branch::query()->create([
            'restaurant_id' => $restaurant->id,
            'code' => 'MAIN',
            'name' => 'Main Branch',
            'slug' => 'main',
            'is_primary' => true,
            'is_active' => true,
        ]);

        $branch->businessHours()->create([
            'day_of_week' => 0,
            'opens_at' => '10:00:00',
            'closes_at' => '23:00:00',
            'is_closed' => false,
        ]);

        $this->assertDatabaseHas('branch_business_hours', [
            'branch_id' => $branch->id,
            'day_of_week' => 0,
            'is_closed' => false,
        ]);
    }
}
