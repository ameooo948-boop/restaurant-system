<?php
namespace App\Console\Commands;
use App\Modules\Inventory\Models\MeasurementUnit;
use App\Modules\Restaurants\Models\Restaurant;
use Illuminate\Console\Command;
class SetupInventoryUnits extends Command
{
 protected $signature='restaurant:inventory:setup-units';
 protected $description='Create standard measurement units for every restaurant';
 public function handle(): int
 {
  $units=[['name'=>'جرام','symbol'=>'g','dimension'=>'weight'],['name'=>'كيلوجرام','symbol'=>'kg','dimension'=>'weight'],['name'=>'ملليلتر','symbol'=>'ml','dimension'=>'volume'],['name'=>'لتر','symbol'=>'L','dimension'=>'volume'],['name'=>'قطعة','symbol'=>'pcs','dimension'=>'count'],['name'=>'دستة','symbol'=>'dozen','dimension'=>'count']];
  $restaurants=Restaurant::query()->orderBy('id')->get();
  foreach($restaurants as $restaurant){foreach($units as $unit){MeasurementUnit::query()->updateOrCreate(['restaurant_id'=>$restaurant->id,'symbol'=>$unit['symbol']], [...$unit,'is_active'=>true]);} $this->line("{$restaurant->name}: standard units ready");}
  $this->info('Inventory units are ready.'); return self::SUCCESS;
 }
}
