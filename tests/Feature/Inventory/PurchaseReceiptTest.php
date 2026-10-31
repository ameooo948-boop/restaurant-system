<?php
namespace Tests\Feature\Inventory;
use App\Modules\Inventory\Services\PurchaseOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
class PurchaseReceiptTest extends TestCase
{
 use RefreshDatabase, InventoryTestSetup;
 public function test_partial_receipt_increases_stock_once_for_same_idempotency_key(): void
 {
  $context=$this->inventoryContext(0,1); $restaurant=$context[0]; $branch=$context[1]; $ingredient=$context[3]; $stock=$context[4]; $user=$context[8];
  $supplier=\App\Modules\Inventory\Models\Supplier::query()->create(['restaurant_id'=>$restaurant->id,'name'=>'Supplier A','is_active'=>true]);
  $service=app(PurchaseOrderService::class);
  $order=$service->create($branch,$supplier,[['ingredient_id'=>$ingredient->id,'quantity_ordered'=>20,'unit_cost'=>4.5]],$user);
  $item=$order->items()->firstOrFail(); $key=(string)Str::uuid();
  $service->receive($order,[$item->id=>10],$user,$key);
  $service->receive($order,[$item->id=>10],$user,$key);
  $this->assertSame('10.000',$stock->fresh()->on_hand_qty);
  $this->assertDatabaseCount('goods_receipts',1);
  $this->assertDatabaseCount('stock_movements',1);
  $this->assertSame('partially_received',$order->fresh()->status);
 }
}
