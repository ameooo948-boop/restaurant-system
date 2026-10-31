<?php
namespace Tests\Feature\Inventory;
use App\Modules\Inventory\Services\InventoryCountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class InventoryCountTest extends TestCase
{
 use RefreshDatabase, InventoryTestSetup;
 public function test_posting_count_adjusts_stock_and_is_idempotent(): void
 {
  $context=$this->inventoryContext(30,1); $branch=$context[1]; $ingredient=$context[3]; $stock=$context[4]; $user=$context[8];
  $service=app(InventoryCountService::class); $count=$service->createDraft($branch,$user);
  $item=$count->items()->where('ingredient_id',$ingredient->id)->firstOrFail();
  $service->updateCountedQuantity($count,$item->id,28,'فرق جرد تجريبي');
  $service->post($count,$user); $service->post($count,$user);
  $this->assertSame('28.000',$stock->fresh()->on_hand_qty);
  $this->assertDatabaseCount('stock_movements',1);
  $this->assertSame('posted',$count->fresh()->status);
 }
}
