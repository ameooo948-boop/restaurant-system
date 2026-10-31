<?php

namespace Tests\Feature\Kitchen;

use Tests\TestCase;

class KitchenRoutesRegisteredTest extends TestCase
{
    public function test_storefront_and_kitchen_routes_are_registered_after_each_application_boot(): void
    {
        $this->assertExpectedRoutesExist();

        $this->refreshApplication();

        $this->assertExpectedRoutesExist();
    }

    private function assertExpectedRoutesExist(): void
    {
        $routes = app('router')->getRoutes();

        foreach ([
            'store.menu.index',
            'store.cart.items.store',
            'store.checkout.store',
            'admin.branches.kitchen.board',
            'admin.branches.kitchen.tickets.status',
            'admin.branches.kitchen.stations.index',
            'admin.branches.kitchen.stations.store',
            'admin.branches.kitchen.stations.update',
            'admin.branches.kitchen.stations.products.sync',
        ] as $routeName) {
            $this->assertNotNull($routes->getByName($routeName), "Missing route: {$routeName}");
        }
    }
}
