<?php
namespace App\Modules\Inventory\Http\Controllers;
use App\Modules\Branches\Models\Branch;
use App\Modules\Inventory\Http\Requests\ReceivePurchaseOrderRequest;
use App\Modules\Inventory\Http\Requests\StorePurchaseOrderRequest;
use App\Modules\Inventory\Models\Ingredient;
use App\Modules\Inventory\Models\PurchaseOrder;
use App\Modules\Inventory\Models\Supplier;
use App\Modules\Inventory\Services\PurchaseOrderService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
class PurchaseOrderController
{
 public function index(Branch $branch): Response { return Inertia::render('Inventory/Purchases/Index',['branch'=>$branch->only(['id','name','code']),'suppliers'=>Supplier::query()->where('restaurant_id',$branch->restaurant_id)->where('is_active',true)->orderBy('name')->get(['id','name']),'ingredients'=>Ingredient::query()->where('restaurant_id',$branch->restaurant_id)->where('is_active',true)->with('baseUnit:id,symbol')->orderBy('name')->get(['id','name','sku','base_unit_id'])->map(fn($i)=>['id'=>$i->id,'name'=>$i->name,'sku'=>$i->sku,'base_unit_symbol'=>$i->baseUnit?->symbol]),'orders'=>PurchaseOrder::query()->where('branch_id',$branch->id)->with(['supplier:id,name','items.ingredient:id,name'])->latest('ordered_at')->paginate(20)->withQueryString()]); }
 public function store(Branch $branch,StorePurchaseOrderRequest $request,PurchaseOrderService $service): RedirectResponse { $data=$request->validated(); $supplier=Supplier::query()->where('restaurant_id',$branch->restaurant_id)->findOrFail($data['supplier_id']); $service->create($branch,$supplier,$data['items'],$request->user(),$data['notes']??null); return back()->with('success','تم إنشاء أمر الشراء.'); }
 public function receive(Branch $branch,int $purchaseOrder,ReceivePurchaseOrderRequest $request,PurchaseOrderService $service): RedirectResponse { $order=PurchaseOrder::query()->where('branch_id',$branch->id)->findOrFail($purchaseOrder); $data=$request->validated(); $items=collect($data['items'])->mapWithKeys(fn($item)=>[$item['purchase_order_item_id']=>$item['received_qty']])->all(); $service->receive($order,$items,$request->user(),$data['idempotency_key'],$data['notes']??null); return back()->with('success','تم تسجيل استلام المشتريات.'); }
}
