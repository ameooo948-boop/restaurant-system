<?php

namespace Tests\Feature\Store;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class StoreRoutesRegisteredTest extends TestCase
{
    public function test_store_cart_checkout_and_tracking_routes_are_registered(): void
    {
        $this->assertStoreRoutesAreRegistered();
    }

    public function test_store_routes_are_registered_again_after_application_reboot(): void
    {
        // Laravel's test lifecycle can boot more than one Application in the
        // same PHP process. Route files must be evaluated on every boot.
        $this->refreshApplication();

        $this->assertStoreRoutesAreRegistered();
    }

    private function assertStoreRoutesAreRegistered(): void
    {
        $routes = app('router')->getRoutes();

        foreach ([
            'store.menu.index',
            'store.cart.show',
            'store.cart.items.store',
            'store.cart.items.update',
            'store.cart.items.destroy',
            'store.checkout.create',
            'store.checkout.store',
            'store.orders.show',
        ] as $routeName) {
            $this->assertNotNull($routes->getByName($routeName), "Missing route: {$routeName}");
        }
    }
}
