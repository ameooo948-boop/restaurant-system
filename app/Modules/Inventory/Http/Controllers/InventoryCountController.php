<?php
namespace App\Modules\Inventory\Http\Controllers;
use App\Modules\Branches\Models\Branch;
use App\Modules\Inventory\Http\Requests\StoreInventoryCountRequest;
use App\Modules\Inventory\Http\Requests\UpdateInventoryCountItemRequest;
use App\Modules\Inventory\Models\InventoryCount;
use App\Modules\Inventory\Services\InventoryCountService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
class InventoryCountController
{
 public function index(Branch $branch): Response { return Inertia::render('Inventory/Counts/Index',['branch'=>$branch->only(['id','name','code']),'counts'=>InventoryCount::query()->where('branch_id',$branch->id)->withCount('items')->latest('counted_at')->paginate(20)->withQueryString()]); }
 public function store(Branch $branch,StoreInventoryCountRequest $request,InventoryCountService $service): RedirectResponse { $service->createDraft($branch,$request->user(),$request->validated('notes')); return back()->with('success','تم إنشاء جرد جديد.'); }
 public function show(Branch $branch,int $count): Response { $record=InventoryCount::query()->where('branch_id',$branch->id)->with('items.ingredient.baseUnit')->findOrFail($count); return Inertia::render('Inventory/Counts/Show',['branch'=>$branch->only(['id','name','code']),'count'=>$record]); }
 public function updateItem(Branch $branch,int $count,int $item,UpdateInventoryCountItemRequest $request,InventoryCountService $service): RedirectResponse { $record=InventoryCount::query()->where('branch_id',$branch->id)->findOrFail($count); $service->updateCountedQuantity($record,$item,(float)$request->validated('counted_qty'),$request->validated('note')); return back()->with('success','تم حفظ الكمية الفعلية.'); }
 public function post(Branch $branch,int $count,\Illuminate\Http\Request $request,InventoryCountService $service): RedirectResponse { $record=InventoryCount::query()->where('branch_id',$branch->id)->findOrFail($count); $service->post($record,$request->user()); return back()->with('success','تم ترحيل الجرد وتسجيل فروق المخزون.'); }
}
