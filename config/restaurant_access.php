<?php

$all = [
    'dashboard.view',
    'branches.view',
    'staff.view',
    'staff.manage',
    'menu.view',
    'menu.manage',
    'menu.catalog.manage',
    'orders.view',
    'orders.create',
    'orders.confirm',
    'orders.update_status',
    'orders.cancel',
    'orders.refund',
    'payments.view',
    'payments.record',
    'kitchen.view',
    'kitchen.tickets.update',
    'inventory.view',
    'inventory.manage',
    'purchases.manage',
    'reports.view',
    'settings.manage',
    'tables.view',
    'tables.manage',
    'deliveries.view',
    'deliveries.update',
];

return [
    'permissions' => $all,
    'roles' => [
        // Restaurant owners can edit the shared restaurant-wide catalog.
        'restaurant_owner' => $all,
        // Branch managers can view the shared catalog but cannot change global products/categories.
        'branch_manager' => array_values(array_diff($all, ['branches.view', 'menu.catalog.manage'])),
        'cashier' => [
            'dashboard.view', 'menu.view', 'orders.view', 'orders.create',
            'orders.confirm', 'orders.update_status', 'orders.cancel',
            'payments.view', 'payments.record', 'tables.view',
        ],
        'kitchen_staff' => [
            'dashboard.view', 'kitchen.view', 'kitchen.tickets.update',
        ],
        'inventory_manager' => [
            'dashboard.view', 'inventory.view', 'inventory.manage', 'purchases.manage',
            'menu.view', 'reports.view',
        ],
        'delivery_staff' => [
            'dashboard.view', 'deliveries.view', 'deliveries.update',
        ],
    ],
];
