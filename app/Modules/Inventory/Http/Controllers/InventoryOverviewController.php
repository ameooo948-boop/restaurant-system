<?php
namespace App\Modules\Inventory\Http\Controllers;
use App\Modules\Branches\Models\Branch;
use App\Modules\Inventory\Models\BranchIngredientStock;
use App\Modules\Inventory\Models\StockMovement;
use Inertia\Inertia;
use Inertia\Response;
class InventoryOverviewController
{
 public function __invoke(Branch $branch): Response
 {
  $stocks=BranchIngredientStock::query()->where('branch_id',$branch->id)->with('ingredient.baseUnit')->orderBy('ingredient_id')->get();
  $low=$stocks->filter(fn($s)=>(float)$s->on_hand_qty-(float)$s->reserved_qty <= (float)($s->reorder_level_override ?? $s->ingredient->reorder_level));
  $movements=StockMovement::query()->where('branch_id',$branch->id)->with('ingredient:id,name')->latest('occurred_at')->limit(12)->get();
  return Inertia::render('Inventory/Overview',[
   'branch'=>$branch->only(['id','name','code']),
   'summary'=>['ingredients'=>$stocks->count(),'low_stock'=>$low->count(),'reserved'=>number_format((float)$stocks->sum('reserved_qty'),3,'.',''),'on_hand_value'=>null],
   'lowStock'=>$low->take(10)->map(fn($s)=>['ingredient_id'=>$s->ingredient_id,'name'=>$s->ingredient->name,'unit'=>$s->ingredient->baseUnit->symbol,'on_hand'=>$s->on_hand_qty,'reserved'=>$s->reserved_qty,'available'=>$s->available_qty,'reorder_level'=>$s->reorder_level_override ?? $s->ingredient->reorder_level])->values(),
   'recentMovements'=>$movements->map(fn($m)=>['id'=>$m->id,'ingredient'=>$m->ingredient?->name,'type'=>$m->movement_type,'quantity_delta'=>$m->quantity_delta,'reference'=>$m->reference,'occurred_at'=>$m->occurred_at?->toIso8601String()])->values(),
  ]);
 }
}
