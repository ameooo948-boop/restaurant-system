<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Modules\Orders\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderTrackingController
{
    public function show(string $publicId, Request $request): Response
    {
        $order = Order::query()->where('public_id', $publicId)
            ->with(['items.modifiers', 'statusHistory'])
            ->firstOrFail();

        $user = $request->user();
        $isOwner = $user && (int) $order->user_id === (int) $user->id;
        $guestToken = $request->session()->get('restaurant.guest_cart_token');
        $isGuestOwner = $order->user_id === null && is_string($guestToken)
            && hash_equals((string) $order->guest_token_hash, hash('sha256', $guestToken));

        abort_unless($isOwner || $isGuestOwner, 404);

        return Inertia::render('Store/Orders/Show', [
            'order' => [
                'public_id' => $order->public_id,
                'order_number' => $order->order_number,
                'order_type' => $order->order_type,
                'order_status' => $order->order_status,
                'payment_status' => $order->payment_status,
                'payment_method' => $order->payment_method,
                'currency' => $order->currency,
                'subtotal' => $order->subtotal,
                'delivery_fee' => $order->delivery_fee,
                'total' => $order->total,
                'customer_name' => $order->customer_name,
                'customer_phone' => $order->customer_phone,
                'address' => $order->delivery_address_snapshot,
                'placed_at' => $order->placed_at?->toIso8601String(),
                'items' => $order->items->map(fn ($item) => [
                    'product_name' => $item->product_name_snapshot,
                    'variant_name' => $item->variant_name_snapshot,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_subtotal' => $item->line_subtotal,
                    'notes' => $item->notes,
                    'modifiers' => $item->modifiers->map(fn ($modifier) => [
                        'group_name' => $modifier->group_name_snapshot,
                        'option_name' => $modifier->option_name_snapshot,
                        'quantity' => $modifier->quantity,
                        'unit_price_delta' => $modifier->unit_price_delta,
                    ])->values(),
                ])->values(),
                'history' => $order->statusHistory->map(fn ($history) => [
                    'status' => $history->new_status,
                    'note' => $history->note,
                    'created_at' => $history->created_at?->toIso8601String(),
                ])->values(),
            ],
        ]);
    }
}
