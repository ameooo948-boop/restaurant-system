<?php
namespace App\Modules\Inventory\Http\Controllers;
use App\Modules\Branches\Models\Branch;
use App\Modules\Inventory\Http\Requests\StoreSupplierRequest;
use App\Modules\Inventory\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
class SupplierController
{
 public function index(Branch $branch): Response { return Inertia::render('Inventory/Suppliers/Index',['branch'=>$branch->only(['id','name','code']),'suppliers'=>Supplier::query()->where('restaurant_id',$branch->restaurant_id)->orderBy('name')->paginate(25)->withQueryString()]); }
 public function store(Branch $branch,StoreSupplierRequest $request): RedirectResponse { Supplier::query()->create([...$request->validated(),'restaurant_id'=>$branch->restaurant_id,'is_active'=>true]); return back()->with('success','تم إضافة المورد.'); }
}
