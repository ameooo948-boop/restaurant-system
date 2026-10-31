<?php

namespace Tests\Feature\Delivery;

use Tests\TestCase;

class DeliveryRoutesRegisteredTest extends TestCase
{
    public function test_delivery_routes_survive_application_refresh(): void
    {
        $this->refreshApplication();
        $routes = app('router')->getRoutes();
        $this->assertNotNull($routes->getByName('admin.branches.deliveries.index'));
        $this->assertNotNull($routes->getByName('admin.branches.delivery-zones.index'));
        $assignRoute = $routes->getByName('admin.branches.deliveries.assign');
        $this->assertNotNull($assignRoute);
        $this->assertContains('App\\Http\\Middleware\\EnsureBranchPermission:deliveries.assign', $assignRoute->gatherMiddleware());
    }
}
