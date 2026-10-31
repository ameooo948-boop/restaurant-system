<?php

namespace App\Console\Commands;

use App\Modules\Reports\Models\ExpenseCategory;
use App\Modules\Restaurants\Models\Restaurant;
use Illuminate\Console\Command;

class SetupReportExpenseCategories extends Command
{
    protected $signature = 'restaurant:reports:setup-categories';
    protected $description = 'Create the default expense categories for each restaurant';

    public function handle(): int
    {
        $defaults = ['إيجار', 'رواتب وأجور', 'مرافق', 'صيانة', 'تغليف ومستهلكات', 'تسويق', 'نقل ومواصلات', 'أخرى'];
        $count = 0;

        Restaurant::query()->orderBy('id')->each(function (Restaurant $restaurant) use ($defaults, &$count): void {
            foreach ($defaults as $name) {
                ExpenseCategory::query()->firstOrCreate(
                    ['restaurant_id' => $restaurant->id, 'name' => $name],
                    ['is_active' => true],
                );
                $count++;
            }
        });

        $this->info("Default expense categories synchronized for {$count} restaurant/category pairs.");

        return self::SUCCESS;
    }
}
