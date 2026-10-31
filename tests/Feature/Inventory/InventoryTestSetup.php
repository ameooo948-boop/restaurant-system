<?php
namespace Tests\Feature\Inventory;
use App\Models\User;
use App\Modules\Branches\Models\Branch;
use App\Modules\Inventory\Models\Ingredient;
use App\Modules\Inventory\Models\MeasurementUnit;
use App\Modules\Inventory\Models\Recipe;
use App\Modules\Inventory\Models\Supplier;
use App\Modules\Inventory\Models\BranchIngredientStock;
use App\Modules\Kitchen\Actions\CreateKitchenTicketsAction;
use App\Modules\Kitchen\Models\KitchenTicket;
use App\Modules\Kitchen\Services\KitchenTicketService;
use App\Modules\Menu\Models\Product;
use App\Modules\Menu\Models\ProductVariant;
use App\Modules\Orders\Models\Order;
use App\Modules\Restaurants\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
trait InventoryTestSetup
{
 private function inventoryContext(float $stockQty=500.0, int $orderQty=2): array
 {
  $restaurant=Restaurant::query()->create(['name'=>'Inventory Test','slug'=>'inventory-'.Str::lower(Str::random(6)),'currency'=>'EGP','timezone'=>'Africa/Cairo','default_locale'=>'ar','supported_locales'=>['ar','en'],'is_active'=>true]);
  $branch=Branch::query()->create(['restaurant_id'=>$restaurant->id,'code'=>'I'.Str::upper(Str::random(4)),'name'=>'Inventory Branch','slug'=>'inventory-'.Str::lower(Str::random(6)),'is_primary'=>true,'is_active'=>true]);
  $unit=MeasurementUnit::query()->create(['restaurant_id'=>$restaurant->id,'name'=>'جرام','symbol'=>'g','dimension'=>'weight','is_active'=>true]);
  $ingredient=Ingredient::query()->create(['restaurant_id'=>$restaurant->id,'base_unit_id'=>$unit->id,'name'=>'Beef','sku'=>'BEEF-'.Str::upper(Str::random(4)),'reorder_level'=>50,'is_active'=>true]);
  $stock=BranchIngredientStock::query()->create(['branch_id'=>$branch->id,'ingredient_id'=>$ingredient->id,'on_hand_qty'=>$stockQty,'reserved_qty'=>0]);
  $product=Product::query()->create(['restaurant_id'=>$restaurant->id,'name'=>'Burger','slug'=>'burger-'.Str::lower(Str::random(6)),'is_active'=>true]);
  $variant=ProductVariant::query()->create(['product_id'=>$product->id,'name'=>'Regular','sku'=>'BUR-'.Str::upper(Str::random(4)),'base_price'=>'100.00','is_default'=>true,'is_active'=>true]);
  $recipe=Recipe::query()->create(['product_variant_id'=>$variant->id,'yield_quantity'=>1,'is_active'=>true]);
  $recipe->items()->create(['ingredient_id'=>$ingredient->id,'quantity'=>100,'waste_percent'=>0]);
  $user=User::query()->create(['name'=>'Inventory User','email'=>'inv-'.Str::lower(Str::random(6)).'@example.com','password'=>'StrongPassword!123','is_active'=>true]);
  $order=Order::query()->create(['public_id'=>(string)Str::uuid(),'restaurant_id'=>$restaurant->id,'branch_id'=>$branch->id,'order_number'=>'INV-'.Str::upper(Str::random(6)),'idempotency_key'=>(string)Str::uuid(),'order_type'=>'pickup','order_status'=>'pending_confirmation','payment_method'=>'cash','payment_status'=>'pending','order_source'=>'website','currency'=>'EGP','subtotal'=>'200.00','discount_total'=>'0.00','tax_total'=>'0.00','delivery_fee'=>'0.00','total'=>'200.00','customer_name'=>'Inventory Guest','customer_phone'=>'01000000000','placed_at'=>now()]);
  $order->items()->create(['product_id'=>$product->id,'product_variant_id'=>$variant->id,'product_name_snapshot'=>'Burger','variant_name_snapshot'=>'Regular','quantity'=>$orderQty,'unit_price'=>'100.00','line_subtotal'=>(string)($orderQty*100)]);
  app(CreateKitchenTicketsAction::class)->execute($order);
  return [$restaurant,$branch,$unit,$ingredient,$stock,$product,$variant,$recipe,$user,$order->fresh(['items','kitchenTickets.items'])];
 }
}
