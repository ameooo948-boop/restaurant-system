<?php

namespace App\Modules\Kitchen\Services;

use App\Models\User;
use App\Modules\Inventory\Services\InventoryStockService;
use App\Modules\Kitchen\Models\KitchenTicket;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderStatusHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KitchenTicketService
{
    public function __construct(private readonly InventoryStockService $inventory) {}

    public function transition(int $branchId, int $ticketId, string $targetStatus, User $user, ?string $note = null): KitchenTicket
    {
        return DB::transaction(function () use ($branchId, $ticketId, $targetStatus, $user, $note): KitchenTicket {
            // Lock the parent order before the ticket. Order operations use the
            // same lock order when cancelling an order, preventing lock inversion.
            $ticketReference = KitchenTicket::query()
                ->where('branch_id', $branchId)
                ->findOrFail($ticketId);

            $order = Order::query()
                ->where('branch_id', $branchId)
                ->lockForUpdate()
                ->findOrFail($ticketReference->order_id);

            $ticket = KitchenTicket::query()
                ->where('branch_id', $branchId)
                ->lockForUpdate()
                ->findOrFail($ticketId);

            if ($order->order_status === OrderStatus::Cancelled->value) {
                throw ValidationException::withMessages([
                    'status' => 'لا يمكن تحريك تذكرة مطبخ لطلب ملغي.',
                ]);
            }

            if ($ticket->status === $targetStatus) {
                return $ticket->load(['station', 'items']);
            }

            $allowedTransitions = [
                'queued' => ['preparing'],
                'preparing' => ['ready'],
            ];

            if (! in_array($targetStatus, $allowedTransitions[$ticket->status] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => 'انتقال حالة تذكرة المطبخ غير مسموح.',
                ]);
            }

            if ($targetStatus === 'preparing') {
                // Reserve first (idempotently), then convert this station's reservation to consumption.
                $this->inventory->reserveForOrder($order);
                $this->inventory->consumeForTicket($ticket, $user);
            }

            $oldTicketStatus = $ticket->status;
            $now = now();
            $ticket->update([
                'status' => $targetStatus,
                'started_at' => $targetStatus === 'preparing' ? ($ticket->started_at ?? $now) : $ticket->started_at,
                'ready_at' => $targetStatus === 'ready' ? $now : $ticket->ready_at,
            ]);

            $ticket->items()->update([
                'status' => $targetStatus,
                'started_at' => $targetStatus === 'preparing' ? $now : DB::raw('started_at'),
                'ready_at' => $targetStatus === 'ready' ? $now : DB::raw('ready_at'),
                'updated_at' => $now,
            ]);

            $ticket->statusHistory()->create([
                'old_status' => $oldTicketStatus,
                'new_status' => $targetStatus,
                'changed_by' => $user->id,
                'source' => 'kitchen_board',
                'note' => $note,
                'created_at' => $now,
            ]);

            $oldOrderStatus = $order->order_status;
            $newOrderStatus = null;

            if ($targetStatus === 'preparing' && in_array($oldOrderStatus, [
                OrderStatus::PendingConfirmation->value,
                OrderStatus::Confirmed->value,
            ], true)) {
                $newOrderStatus = OrderStatus::Preparing->value;
                $order->update([
                    'order_status' => $newOrderStatus,
                    'confirmed_at' => $order->confirmed_at ?? $now,
                ]);
            }

            if ($targetStatus === 'ready') {
                $allTicketsReady = ! KitchenTicket::query()
                    ->where('order_id', $order->id)
                    ->where('status', '!=', 'ready')
                    ->exists();

                if ($allTicketsReady && ! in_array($oldOrderStatus, [
                    OrderStatus::Cancelled->value,
                    OrderStatus::Completed->value,
                    OrderStatus::Ready->value,
                ], true)) {
                    $newOrderStatus = OrderStatus::Ready->value;
                    $order->update(['order_status' => $newOrderStatus]);
                }
            }

            if ($newOrderStatus !== null && $newOrderStatus !== $oldOrderStatus) {
                OrderStatusHistory::query()->create([
                    'order_id' => $order->id,
                    'old_status' => $oldOrderStatus,
                    'new_status' => $newOrderStatus,
                    'changed_by' => $user->id,
                    'source' => 'kitchen',
                    'note' => $targetStatus === 'preparing'
                        ? 'بدأ المطبخ تجهيز الطلب.'
                        : 'أصبحت جميع تذاكر المطبخ جاهزة.',
                    'created_at' => $now,
                ]);
            }

            return $ticket->fresh(['station', 'items', 'statusHistory']);
        }, 3);
    }
}
