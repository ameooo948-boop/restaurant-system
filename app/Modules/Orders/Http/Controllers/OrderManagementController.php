<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Modules\Branches\Models\Branch;
use App\Modules\Orders\Http\Requests\OrderIndexRequest;
use App\Modules\Orders\Http\Requests\UpdateOrderStatusRequest;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\OrderManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderManagementController
{
    public function index(Branch $branch, OrderIndexRequest $request): Response
    {
        $filters = $request->validated();

        $orders = Order::query()
            ->where('branch_id', $branch->id)
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('order_status', $status))
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('order_type', $type))
            ->when($filters['payment_status'] ?? null, fn ($query, $status) => $query->where('payment_status', $status))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('placed_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('placed_at', '<=', $to))
            ->when($filters['q'] ?? null, function ($query, string $search): void {
                $query->where(function ($nested) use ($search): void {
                    $pattern = '%'.$search.'%';
                    $nested->whereRaw('LOWER(order_number) LIKE LOWER(?)', [$pattern])
                        ->orWhereRaw('LOWER(customer_name) LIKE LOWER(?)', [$pattern])
                        ->orWhereRaw('LOWER(customer_phone) LIKE LOWER(?)', [$pattern]);
                });
            })
            ->withCount('items')
            ->orderByDesc('placed_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Order $order): array => $this->orderSummary($order));

        return Inertia::render('Admin/Orders/Index', [
            'branch' => $branch->only(['id', 'code', 'name']),
            'orders' => $orders,
            'filters' => array_merge(['q' => '', 'status' => '', 'type' => '', 'payment_status' => '', 'from' => '', 'to' => ''], $filters),
            'statusOptions' => $this->statusOptions(),
            'typeOptions' => [
                ['value' => 'delivery', 'label' => 'توصيل'],
                ['value' => 'pickup', 'label' => 'استلام'],
                ['value' => 'dine_in', 'label' => 'داخل المطعم'],
            ],
        ]);
    }

    public function show(Branch $branch, int $order, Request $request, OrderManagementService $service): Response
    {
        $record = Order::query()
            ->where('branch_id', $branch->id)
            ->with([
                'items.modifiers',
                'statusHistory.changedBy:id,name',
                'payments',
                'kitchenTickets.station:id,name,code',
                'kitchenTickets.items:id,kitchen_ticket_id,product_name_snapshot,variant_name_snapshot,quantity,status',
            ])
            ->findOrFail($order);

        return Inertia::render('Admin/Orders/Show', [
            'branch' => $branch->only(['id', 'code', 'name']),
            'order' => [
                'id' => $record->id,
                'public_id' => $record->public_id,
                'order_number' => $record->order_number,
                'order_type' => $record->order_type,
                'order_status' => $record->order_status,
                'payment_method' => $record->payment_method,
                'payment_status' => $record->payment_status,
                'currency' => $record->currency,
                'subtotal' => $record->subtotal,
                'discount_total' => $record->discount_total,
                'tax_total' => $record->tax_total,
                'delivery_fee' => $record->delivery_fee,
                'total' => $record->total,
                'customer_name' => $record->customer_name,
                'customer_phone' => $record->customer_phone,
                'customer_email' => $record->customer_email,
                'address' => $record->delivery_address_snapshot,
                'table_label' => $record->table_label,
                'customer_notes' => $record->customer_notes,
                'placed_at' => $record->placed_at?->toIso8601String(),
                'items' => $record->items->map(fn ($item): array => [
                    'id' => $item->id,
                    'product_name' => $item->product_name_snapshot,
                    'variant_name' => $item->variant_name_snapshot,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_subtotal' => $item->line_subtotal,
                    'notes' => $item->notes,
                    'modifiers' => $item->modifiers->map(fn ($modifier): array => [
                        'group_name' => $modifier->group_name_snapshot,
                        'option_name' => $modifier->option_name_snapshot,
                        'quantity' => $modifier->quantity,
                        'unit_price_delta' => $modifier->unit_price_delta,
                        'line_total' => $modifier->line_total,
                    ])->values(),
                ])->values(),
                'payments' => $request->user()->can('payments.view') ? $record->payments->map(fn ($payment): array => [
                    'id' => $payment->id,
                    'method' => $payment->method,
                    'provider' => $payment->provider,
                    'status' => $payment->status,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'paid_at' => $payment->paid_at?->toIso8601String(),
                    'manual_review_required' => (bool) data_get($payment->metadata, 'manual_review_required', false),
                    'manual_review_reason' => data_get($payment->metadata, 'manual_review_reason'),
                ])->values() : [],
                'history' => $record->statusHistory->map(fn ($history): array => [
                    'old_status' => $history->old_status,
                    'new_status' => $history->new_status,
                    'note' => $history->note,
                    'source' => $history->source,
                    'changed_by' => $history->changedBy?->name,
                    'created_at' => $history->created_at?->toIso8601String(),
                ])->values(),
                'kitchen_tickets' => $record->kitchenTickets->map(fn ($ticket): array => [
                    'id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'status' => $ticket->status,
                    'station' => $ticket->station?->only(['id', 'name', 'code']),
                    'items' => $ticket->items->map(fn ($item): array => [
                        'product_name' => $item->product_name_snapshot,
                        'variant_name' => $item->variant_name_snapshot,
                        'quantity' => $item->quantity,
                        'status' => $item->status,
                    ])->values(),
                ])->values(),
            ],
            'allowedTransitions' => $service->availableTransitions($record),
            'permissions' => [
                'confirm' => $request->user()->can('orders.confirm'),
                'update_status' => $request->user()->can('orders.update_status'),
                'cancel' => $request->user()->can('orders.cancel'),
                'view_payments' => $request->user()->can('payments.view'),
                'record_payment' => $request->user()->can('payments.record'),
            ],
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function updateStatus(
        Branch $branch,
        int $order,
        UpdateOrderStatusRequest $request,
        OrderManagementService $service,
    ): RedirectResponse {
        $target = $request->validated('status');

        if ($target === 'confirmed') {
            abort_unless($request->user()->can('orders.confirm'), 403);
        } elseif ($target === 'cancelled') {
            abort_unless($request->user()->can('orders.cancel'), 403);
        } else {
            abort_unless($request->user()->can('orders.update_status'), 403);
        }

        $service->transition(
            $branch->id,
            $order,
            $target,
            $request->user(),
            $request->validated('note'),
        );

        return back()->with('success', 'تم تحديث الطلب بنجاح.');
    }

    /** @return array<string, mixed> */
    private function orderSummary(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'order_type' => $order->order_type,
            'order_status' => $order->order_status,
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'total' => $order->total,
            'currency' => $order->currency,
            'items_count' => $order->items_count,
            'placed_at' => $order->placed_at?->toIso8601String(),
        ];
    }

    /** @return list<array{value:string,label:string}> */
    private function statusOptions(): array
    {
        return [
            ['value' => 'pending_confirmation', 'label' => 'بانتظار التأكيد'],
            ['value' => 'confirmed', 'label' => 'تم التأكيد'],
            ['value' => 'preparing', 'label' => 'قيد التحضير'],
            ['value' => 'ready', 'label' => 'جاهز'],
            ['value' => 'out_for_delivery', 'label' => 'خرج للتوصيل'],
            ['value' => 'delivered', 'label' => 'تم التسليم'],
            ['value' => 'completed', 'label' => 'مكتمل'],
            ['value' => 'cancelled', 'label' => 'ملغي'],
        ];
    }
}
