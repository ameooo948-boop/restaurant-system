<?php

namespace Tests\Feature\Menu;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MenuRoutesRegisteredTest extends TestCase
{
    public function test_all_menu_administration_routes_are_registered(): void
    {
        $routeNames = [
            'admin.branches.menu.categories.index',
            'admin.branches.menu.categories.store',
            'admin.branches.menu.categories.update',
            'admin.branches.menu.categories.destroy',
            'admin.branches.menu.products.index',
            'admin.branches.menu.products.store',
            'admin.branches.menu.products.update',
            'admin.branches.menu.products.destroy',
            'admin.branches.menu.modifiers.index',
            'admin.branches.menu.modifiers.store',
            'admin.branches.menu.modifiers.update',
            'admin.branches.menu.modifiers.destroy',
        ];

        foreach ($routeNames as $routeName) {
            $this->assertTrue(
                Route::has($routeName),
                "Expected menu route [{$routeName}] to be registered.",
            );
        }
    }
}
