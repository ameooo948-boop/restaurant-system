<?php

namespace App\Console\Commands;

use App\Modules\Branches\Models\Branch;
use App\Modules\Restaurants\Models\Restaurant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateFirstRestaurantBranch extends Command
{
    protected $signature = 'restaurant:setup:first-branch';
    protected $description = 'Create the initial restaurant and its primary branch';

    public function handle(): int
    {
        if (Restaurant::query()->exists()) {
            $this->error('A restaurant already exists. This setup command is for the initial record only.');
            return self::FAILURE;
        }

        $restaurantName = trim((string) $this->ask('Restaurant name'));
        $branchName = trim((string) $this->ask('Primary branch name'));
        $branchCode = Str::upper(trim((string) $this->ask('Branch code', 'MAIN')));

        if ($restaurantName === '' || $branchName === '' || $branchCode === '') {
            $this->error('Restaurant name, branch name, and branch code are required.');
            return self::FAILURE;
        }

        $phone = $this->ask('Branch phone (optional)');
        $address = $this->ask('Branch address (optional)');
        $city = $this->ask('City (optional)', '');
        $area = $this->ask('Area (optional)', '');

        DB::transaction(function () use ($restaurantName, $branchName, $branchCode, $phone, $address, $city, $area): void {
            $restaurant = Restaurant::create([
                'name' => $restaurantName,
                'slug' => Str::slug($restaurantName),
                'currency' => 'EGP',
                'timezone' => 'Africa/Cairo',
                'default_locale' => 'ar',
                'supported_locales' => ['ar', 'en'],
                'is_active' => true,
            ]);

            Branch::create([
                'restaurant_id' => $restaurant->id,
                'code' => $branchCode,
                'name' => $branchName,
                'slug' => Str::slug($branchName),
                'phone' => $phone ?: null,
                'address_line' => $address ?: null,
                'city' => $city ?: null,
                'area' => $area ?: null,
                'is_primary' => true,
                'is_active' => true,
            ]);
        });

        $this->info('Restaurant and primary branch created successfully.');
        $this->comment('Next: php artisan restaurant:access:sync');

        return self::SUCCESS;
    }
}
