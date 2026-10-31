<?php
namespace App\Modules\Inventory\Http\Controllers;
use App\Modules\Branches\Models\Branch;
use App\Modules\Inventory\Http\Requests\AdjustStockRequest;
use App\Modules\Inventory\Models\Ingredient;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Services\InventoryStockService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
class StockController
{
 public function index(Branch $branch): Response
 {
  $stocks=Ingredient::query()->where('restaurant_id',$branch->restaurant_id)->where('is_active',true)->with(['baseUnit:id,name,symbol','stocks'=>fn($q)=>$q->where('branch_id',$branch->id)])->orderBy('name')->paginate(30)->withQueryString();
  $movements=StockMovement::query()->where('branch_id',$branch->id)->with('ingredient:id,name')->latest('occurred_at')->paginate(30,['*'],'movements_page')->withQueryString();
  return Inertia::render('Inventory/Stock/Index',['branch'=>$branch->only(['id','name','code']),'ingredients'=>$stocks,'movements'=>$movements]);
 }
 public function adjust(Branch $branch,int $ingredient,AdjustStockRequest $request,InventoryStockService $service): RedirectResponse
 {
  $model=Ingredient::query()->where('restaurant_id',$branch->restaurant_id)->findOrFail($ingredient);
  $service->adjust($branch,$model,(float)$request->validated('quantity_delta'),$request->user(),$request->validated('reason'));
  return back()->with('success','تم تسجيل تسوية المخزون.');
 }
}
