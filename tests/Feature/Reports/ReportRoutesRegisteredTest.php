<?php

namespace Tests\Feature\Reports;

use Tests\TestCase;

class ReportRoutesRegisteredTest extends TestCase
{
    public function test_report_and_expense_routes_survive_application_refresh(): void
    {
        $this->refreshApplication();
        $routes = app('router')->getRoutes();

        foreach ([
            'admin.branches.reports.index',
            'admin.branches.reports.export',
            'admin.branches.expenses.index',
            'admin.branches.expenses.store',
            'admin.branches.expenses.void',
        ] as $name) {
            $this->assertNotNull($routes->getByName($name), "Missing route: {$name}");
        }

        $reportRoute = $routes->getByName('admin.branches.reports.index');
        $this->assertContains(\App\Http\Middleware\EnsureBranchAccess::class, $reportRoute->gatherMiddleware());
        $this->assertContains(\App\Http\Middleware\EnsureBranchPermission::class.':reports.view', $reportRoute->gatherMiddleware());

        $expenseRoute = $routes->getByName('admin.branches.expenses.store');
        $this->assertContains(\App\Http\Middleware\EnsureBranchPermission::class.':expenses.manage', $expenseRoute->gatherMiddleware());
    }
}
