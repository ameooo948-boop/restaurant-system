<?php
namespace Tests\Feature\Inventory;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Services\InventoryStockService;
use LogicException;
use App\Modules\Kitchen\Models\KitchenTicket;
use App\Modules\Kitchen\Services\KitchenTicketService;
use App\Modules\Orders\Services\OrderManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
class InventoryStockWorkflowTest extends TestCase
{
 use RefreshDatabase, InventoryTestSetup;
 public function test_order_confirmation_reserves_and_kitchen_start_consumes_recipe_stock_once(): void
 {
  $context=$this->inventoryContext(500,2); $branch=$context[1]; $stock=$context[4]; $user=$context[8]; $order=$context[9];
  app(OrderManagementService::class)->transition($branch->id,$order->id,'confirmed',$user);
  $this->assertSame('200.000',$stock->fresh()->reserved_qty);
  $ticket=KitchenTicket::query()->where('order_id',$order->id)->firstOrFail();
  app(KitchenTicketService::class)->transition($branch->id,$ticket->id,'preparing',$user);
  $stock->refresh();
  $this->assertSame('300.000',$stock->on_hand_qty);
  $this->assertSame('0.000',$stock->reserved_qty);
  $this->assertDatabaseHas('stock_reservations',['kitchen_ticket_id'=>$ticket->id,'ingredient_id'=>$stock->ingredient_id,'status'=>'consumed','consumed_qty'=>'200.000']);
  $this->assertDatabaseCount('stock_movements',1);
  app(KitchenTicketService::class)->transition($branch->id,$ticket->id,'preparing',$user);
  $this->assertDatabaseCount('stock_movements',1);
 }

 public function test_cancelling_confirmed_order_releases_reserved_ingredients(): void
 {
  $context=$this->inventoryContext(500,2); $branch=$context[1]; $stock=$context[4]; $user=$context[8]; $order=$context[9];
  $management=app(OrderManagementService::class);
  $management->transition($branch->id,$order->id,'confirmed',$user);
  $this->assertSame('200.000',$stock->fresh()->reserved_qty);
  $management->transition($branch->id,$order->id,'cancelled',$user,'إلغاء قبل التحضير');
  $this->assertSame('0.000',$stock->fresh()->reserved_qty);
  $this->assertDatabaseHas('stock_reservations',['order_id'=>$order->id,'status'=>'released']);
  $this->assertDatabaseCount('stock_movements',0);
 }

 public function test_confirmation_fails_without_enough_ingredient_stock(): void
 {
  $context=$this->inventoryContext(50,2); $branch=$context[1]; $user=$context[8]; $order=$context[9];
  try { app(OrderManagementService::class)->transition($branch->id,$order->id,'confirmed',$user); $this->fail('Expected insufficient stock validation.'); }
  catch(ValidationException $exception){$this->assertArrayHasKey('inventory',$exception->errors());}
  $this->assertSame('pending_confirmation',$order->fresh()->order_status);
 }

 public function test_stock_movement_ledger_records_cannot_be_updated_or_deleted(): void
 {
  $context=$this->inventoryContext(100,1); $branch=$context[1]; $ingredient=$context[3]; $user=$context[8];
  $movement=StockMovement::query()->create([
   'branch_id'=>$branch->id,'ingredient_id'=>$ingredient->id,'movement_type'=>'opening',
   'quantity_delta'=>100,'source_type'=>'opening_balance','note'=>'Initial stock',
   'created_by'=>$user->id,'occurred_at'=>now(),
  ]);
  try { $movement->update(['note'=>'tampered']); $this->fail('Expected immutable ledger update to be rejected.'); }
  catch (LogicException $exception) { $this->assertStringContainsString('immutable',$exception->getMessage()); }
  try { $movement->delete(); $this->fail('Expected immutable ledger delete to be rejected.'); }
  catch (LogicException $exception) { $this->assertStringContainsString('cannot be deleted',$exception->getMessage()); }
  $this->assertDatabaseHas('stock_movements',['id'=>$movement->id,'note'=>'Initial stock']);
 }

 public function test_recipe_edits_after_first_reservation_do_not_change_existing_order_reservations(): void
 {
  $context=$this->inventoryContext(500,2); $branch=$context[1]; $stock=$context[4]; $user=$context[8]; $order=$context[9]; $recipe=$context[7];
  app(OrderManagementService::class)->transition($branch->id,$order->id,'confirmed',$user);
  $this->assertSame('200.000',$stock->fresh()->reserved_qty);
  $recipe->items()->firstOrFail()->update(['quantity'=>250]);
  app(InventoryStockService::class)->reserveForOrder($order->fresh());
  $this->assertSame('200.000',$stock->fresh()->reserved_qty);
  $this->assertDatabaseHas('stock_reservations',['order_id'=>$order->id,'ingredient_id'=>$stock->ingredient_id,'reserved_qty'=>'200.000']);
 }
}
