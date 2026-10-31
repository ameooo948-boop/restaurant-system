<?php
namespace App\Modules\Inventory\Http\Controllers;
use App\Modules\Branches\Models\Branch;
use App\Modules\Inventory\Http\Requests\SaveRecipeRequest;
use App\Modules\Inventory\Http\Requests\StoreIngredientRequest;
use App\Modules\Inventory\Models\Ingredient;
use App\Modules\Inventory\Models\MeasurementUnit;
use App\Modules\Inventory\Models\Recipe;
use App\Modules\Menu\Models\ModifierOption;
use App\Modules\Menu\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
class IngredientController
{
 public function index(Branch $branch): Response
 {
  $ingredients=Ingredient::query()->where('restaurant_id',$branch->restaurant_id)->with(['baseUnit:id,name,symbol,dimension','conversions.unit:id,name,symbol,dimension','stocks'=>fn($q)=>$q->where('branch_id',$branch->id)])->orderBy('name')->paginate(25)->withQueryString();
  $variants=ProductVariant::query()->whereHas('product',fn($q)=>$q->where('restaurant_id',$branch->restaurant_id))->with(['product:id,name','recipe.items'])->orderBy('product_id')->get(['id','product_id','name','sku']);
  $modifierOptions=ModifierOption::query()->whereHas('group',fn($q)=>$q->where('restaurant_id',$branch->restaurant_id))->with(['group:id,name','ingredientUsages'])->orderBy('modifier_group_id')->get()->map(fn($o)=>['id'=>$o->id,'label'=>$o->group->name.' - '.$o->name,'items'=>$o->ingredientUsages->map(fn($u)=>['ingredient_id'=>$u->ingredient_id,'quantity_per_option'=>$u->quantity_per_option])->values()]);
  return Inertia::render('Inventory/Ingredients/Index',['branch'=>$branch->only(['id','name','code']),'ingredients'=>$ingredients,'units'=>MeasurementUnit::query()->where('restaurant_id',$branch->restaurant_id)->where('is_active',true)->orderBy('name')->get(['id','name','symbol','dimension']),'variants'=>$variants->map(fn($v)=>['id'=>$v->id,'label'=>$v->product->name.' - '.$v->name,'yield_quantity'=>$v->recipe?->yield_quantity ?? '1.000','items'=>$v->recipe?->items->map(fn($i)=>['ingredient_id'=>$i->ingredient_id,'quantity'=>$i->quantity,'waste_percent'=>$i->waste_percent])->values() ?? []])->values(),'modifierOptions'=>$modifierOptions]);
 }
 public function store(Branch $branch,StoreIngredientRequest $request): RedirectResponse
 {
  $data=$request->validated();
  Ingredient::query()->create([...$data,'restaurant_id'=>$branch->restaurant_id,'reorder_level'=>$data['reorder_level']??0,'is_active'=>$data['is_active']??true]);
  return back()->with('success','تم إنشاء المكون.');
 }
 public function update(Branch $branch,int $ingredient,StoreIngredientRequest $request): RedirectResponse
 {
  $model=Ingredient::query()->where('restaurant_id',$branch->restaurant_id)->findOrFail($ingredient); $data=$request->validated();
  if((int)$data['base_unit_id']!== (int)$model->base_unit_id && $model->stocks()->exists()) throw ValidationException::withMessages(['base_unit_id'=>'لا يمكن تغيير وحدة الأساس بعد بدء تسجيل أرصدة للمكون.']);
  $model->update($data); return back()->with('success','تم تحديث المكون.');
 }
 public function saveRecipe(Branch $branch,int $variant,SaveRecipeRequest $request): RedirectResponse
 {
  $productVariant=ProductVariant::query()->whereHas('product',fn($q)=>$q->where('restaurant_id',$branch->restaurant_id))->findOrFail($variant);
  $data=$request->validated(); $ids=collect($data['items'])->pluck('ingredient_id')->map(fn($id)=>(int)$id)->unique();
  if(Ingredient::query()->where('restaurant_id',$branch->restaurant_id)->whereIn('id',$ids)->count()!==$ids->count()) throw ValidationException::withMessages(['items'=>'الوصفة تحتوي على مكون لا يتبع المطعم.']);
  DB::transaction(function()use($productVariant,$data):void{ $recipe=Recipe::query()->updateOrCreate(['product_variant_id'=>$productVariant->id],['yield_quantity'=>$data['yield_quantity'],'instructions'=>$data['instructions']??null,'is_active'=>true]); $recipe->items()->delete(); foreach($data['items'] as $item){$recipe->items()->create(['ingredient_id'=>$item['ingredient_id'],'quantity'=>$item['quantity'],'waste_percent'=>$item['waste_percent']??0]);}});
  return back()->with('success','تم حفظ وصفة المنتج.');
 }
 public function saveConversions(Branch $branch,int $ingredient,Request $request): RedirectResponse
 {
  $model=Ingredient::query()->where('restaurant_id',$branch->restaurant_id)->with('baseUnit')->findOrFail($ingredient);
  $data=$request->validate(['conversions'=>'required|array|max:20','conversions.*.unit_id'=>'required|integer|distinct|exists:measurement_units,id','conversions.*.to_base_factor'=>'required|numeric|gt:0|max:999999999']);
  $unitIds=collect($data['conversions'])->pluck('unit_id')->map(fn($id)=>(int)$id)->unique();
  $units=MeasurementUnit::query()->where('restaurant_id',$branch->restaurant_id)->where('dimension',$model->baseUnit->dimension)->whereIn('id',$unitIds)->get();
  if($units->count()!==$unitIds->count()) throw ValidationException::withMessages(['conversions'=>'وحدات التحويل يجب أن تتبع المطعم ونفس بُعد وحدة الأساس.']);
  DB::transaction(function()use($model,$data):void{$model->conversions()->delete();foreach($data['conversions'] as $conversion){if((int)$conversion['unit_id']===(int)$model->base_unit_id)continue;$model->conversions()->create(['unit_id'=>$conversion['unit_id'],'to_base_factor'=>$conversion['to_base_factor']]);}});
  return back()->with('success','تم حفظ تحويلات وحدات المكون.');
 }
 public function saveModifierIngredients(Branch $branch,int $option,Request $request): RedirectResponse
 {
  abort_unless($request->user()->can('inventory.manage'),403);
  $data=$request->validate(['items'=>'required|array|max:50','items.*.ingredient_id'=>'required|integer|distinct|exists:ingredients,id','items.*.quantity_per_option'=>'required|numeric|gt:0|max:999999']);
  $modifier=ModifierOption::query()->whereHas('group',fn($q)=>$q->where('restaurant_id',$branch->restaurant_id))->findOrFail($option);
  $ids=collect($data['items'])->pluck('ingredient_id')->map(fn($id)=>(int)$id)->unique();
  if(Ingredient::query()->where('restaurant_id',$branch->restaurant_id)->whereIn('id',$ids)->count()!==$ids->count()) throw ValidationException::withMessages(['items'=>'يوجد مكون خارج المطعم.']);
  DB::transaction(function()use($modifier,$data):void{$modifier->ingredientUsages()->delete();foreach($data['items'] as $item){$modifier->ingredientUsages()->create(['ingredient_id'=>$item['ingredient_id'],'quantity_per_option'=>$item['quantity_per_option']]);}});
  return back()->with('success','تم ربط مكونات الإضافة.');
 }
}
