<?php
namespace Tests\Feature\Inventory;
use Tests\TestCase;
class InventoryRoutesRegisteredTest extends TestCase
{
 public function test_inventory_routes_survive_a_fresh_application_boot(): void
 {
  $this->assertRoutes(); $this->refreshApplication(); $this->assertRoutes();
 }
 private function assertRoutes(): void
 {
  $routes=app('router')->getRoutes();
  foreach(['admin.branches.inventory.overview','admin.branches.inventory.ingredients.index','admin.branches.inventory.stock.index','admin.branches.inventory.suppliers.index','admin.branches.inventory.purchases.index','admin.branches.inventory.counts.index','admin.branches.inventory.counts.show','admin.branches.inventory.counts.post'] as $name){$this->assertNotNull($routes->getByName($name),"Missing inventory route: {$name}");}
 }
}
