<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class OrderRoutesRegisteredTest extends TestCase
{
    public function test_storefront_kitchen_and_admin_routes_are_registered_after_each_application_boot(): void
    {
        $this->assertRoutesExist();
        $this->refreshApplication();
        $this->assertRoutesExist();
    }

    private function assertRoutesExist(): void
    {
        $routes = app('router')->getRoutes();

        foreach ([
            'store.cart.items.store',
            'store.checkout.store',
            'admin.branches.kitchen.board',
            'admin.branches.orders.index',
            'admin.branches.orders.show',
            'admin.branches.orders.status',
        ] as $name) {
            $this->assertNotNull($routes->getByName($name), "Missing route: {$name}");
        }
    }
}
