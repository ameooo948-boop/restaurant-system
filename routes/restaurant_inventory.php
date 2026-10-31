<?php
use App\Http\Middleware\EnsureBranchAccess;
use App\Http\Middleware\EnsureBranchPermission;
use App\Modules\Inventory\Http\Controllers\InventoryCountController;
use App\Modules\Inventory\Http\Controllers\InventoryOverviewController;
use App\Modules\Inventory\Http\Controllers\IngredientController;
use App\Modules\Inventory\Http\Controllers\PurchaseOrderController;
use App\Modules\Inventory\Http\Controllers\StockController;
use App\Modules\Inventory\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', EnsureBranchAccess::class])
 ->prefix('/admin/branches/{branch}/inventory')->name('admin.branches.inventory.')
 ->group(function (): void {
  Route::get('/', InventoryOverviewController::class)->middleware(EnsureBranchPermission::class.':inventory.view')->name('overview');
  Route::get('/ingredients', [IngredientController::class,'index'])->middleware(EnsureBranchPermission::class.':inventory.view')->name('ingredients.index');
  Route::post('/ingredients', [IngredientController::class,'store'])->middleware(EnsureBranchPermission::class.':inventory.manage')->name('ingredients.store');
  Route::put('/ingredients/{ingredient}', [IngredientController::class,'update'])->whereNumber('ingredient')->middleware(EnsureBranchPermission::class.':inventory.manage')->name('ingredients.update');
  Route::put('/ingredients/{ingredient}/conversions', [IngredientController::class,'saveConversions'])->whereNumber('ingredient')->middleware(EnsureBranchPermission::class.':inventory.manage')->name('ingredients.conversions');
  Route::put('/recipes/{variant}', [IngredientController::class,'saveRecipe'])->whereNumber('variant')->middleware(EnsureBranchPermission::class.':inventory.manage')->name('recipes.save');
  Route::put('/modifier-options/{option}/ingredients', [IngredientController::class,'saveModifierIngredients'])->whereNumber('option')->middleware(EnsureBranchPermission::class.':inventory.manage')->name('modifier-ingredients.save');
  Route::get('/stock', [StockController::class,'index'])->middleware(EnsureBranchPermission::class.':inventory.view')->name('stock.index');
  Route::post('/stock/{ingredient}/adjustments', [StockController::class,'adjust'])->whereNumber('ingredient')->middleware(EnsureBranchPermission::class.':inventory.manage')->name('stock.adjust');
  Route::get('/suppliers', [SupplierController::class,'index'])->middleware(EnsureBranchPermission::class.':purchases.manage')->name('suppliers.index');
  Route::post('/suppliers', [SupplierController::class,'store'])->middleware(EnsureBranchPermission::class.':purchases.manage')->name('suppliers.store');
  Route::get('/purchases', [PurchaseOrderController::class,'index'])->middleware(EnsureBranchPermission::class.':purchases.manage')->name('purchases.index');
  Route::post('/purchases', [PurchaseOrderController::class,'store'])->middleware(EnsureBranchPermission::class.':purchases.manage')->name('purchases.store');
  Route::post('/purchases/{purchaseOrder}/receive', [PurchaseOrderController::class,'receive'])->whereNumber('purchaseOrder')->middleware(EnsureBranchPermission::class.':purchases.manage')->name('purchases.receive');
  Route::get('/counts', [InventoryCountController::class,'index'])->middleware(EnsureBranchPermission::class.':inventory.view')->name('counts.index');
  Route::post('/counts', [InventoryCountController::class,'store'])->middleware(EnsureBranchPermission::class.':inventory.manage')->name('counts.store');
  Route::get('/counts/{count}', [InventoryCountController::class,'show'])->whereNumber('count')->middleware(EnsureBranchPermission::class.':inventory.view')->name('counts.show');
  Route::patch('/counts/{count}/items/{item}', [InventoryCountController::class,'updateItem'])->whereNumber('count')->whereNumber('item')->middleware(EnsureBranchPermission::class.':inventory.manage')->name('counts.items.update');
  Route::post('/counts/{count}/post', [InventoryCountController::class,'post'])->whereNumber('count')->middleware(EnsureBranchPermission::class.':inventory.manage')->name('counts.post');
 });
