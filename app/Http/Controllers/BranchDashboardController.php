<?php

namespace App\Http\Controllers;

use App\Modules\Branches\Models\Branch;
use App\Modules\Orders\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BranchDashboardController
{
    public function __invoke(Request $request, Branch $branch): Response
    {
        $user = $request->user();
        $canViewOrders = $user?->can('orders.view') ?? false;
        $stats = null;
        $recentOrders = [];

        if ($canViewOrders) {
            $base = Order::query()->where('branch_id', $branch->id);
            $today = now()->toDateString();

            $stats = [
                'today_orders' => (clone $base)->whereDate('placed_at', $today)->count(),
                'pending_confirmation' => (clone $base)->where('order_status', 'pending_confirmation')->count(),
                'preparing' => (clone $base)->where('order_status', 'preparing')->count(),
                'ready' => (clone $base)->where('order_status', 'ready')->count(),
                'out_for_delivery' => (clone $base)->where('order_status', 'out_for_delivery')->count(),
                'paid_today' => (clone $base)->whereDate('placed_at', $today)
                    ->where('payment_status', 'succeeded')->sum('total'),
            ];

            $recentOrders = (clone $base)
                ->withCount('items')
                ->orderByDesc('placed_at')
                ->limit(7)
                ->get()
                ->map(fn (Order $order): array => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->customer_name,
                    'order_status' => $order->order_status,
                    'order_type' => $order->order_type,
                    'total' => $order->total,
                    'currency' => $order->currency,
                    'items_count' => $order->items_count,
                    'placed_at' => $order->placed_at?->toIso8601String(),
                ])->values()->all();
        }

        return Inertia::render('Admin/Dashboard', [
            'branch' => $branch->only(['id', 'code', 'name']),
            'permissions' => $user?->getAllPermissions()->pluck('name')->values() ?? collect(),
            'stats' => $stats,
            'recentOrders' => $recentOrders,
        ]);
    }
}
