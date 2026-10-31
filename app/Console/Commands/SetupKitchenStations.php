<?php

namespace App\Console\Commands;

use App\Modules\Branches\Models\Branch;
use App\Modules\Kitchen\Actions\CreateKitchenTicketsAction;
use Illuminate\Console\Command;

class SetupKitchenStations extends Command
{
    protected $signature = 'restaurant:kitchen:setup';
    protected $description = 'Create the default kitchen station for each active branch';

    public function handle(CreateKitchenTicketsAction $action): int
    {
        $branches = Branch::query()->active()->orderBy('id')->get();

        if ($branches->isEmpty()) {
            $this->warn('No active branches found.');
            return self::SUCCESS;
        }

        foreach ($branches as $branch) {
            $station = $action->ensureDefaultStation($branch);
            $this->line("{$branch->code}: {$station->name} ({$station->code})");
        }

        $this->info('Default kitchen stations are ready.');
        return self::SUCCESS;
    }
}
