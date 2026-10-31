<?php

namespace App\Modules\Orders\Services;

use App\Models\User;
use App\Modules\Kitchen\Models\KitchenTicket;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Enums\OrderType;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderStatusHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderManagementService
{
    /** @return list<string> */
    public function availableTransitions(Order $order): array
    {
        $current = $order->order_status;

        return match ($current) {
            OrderStatus::PendingConfirmation->value => [
                OrderStatus::Confirmed->value,
                ...($this->canCancelBeforePreparation($order) ? [OrderStatus::Cancelled->value] : []),
            ],
            OrderStatus::Confirmed->value => $this->canCancelBeforePreparation($order)
                ? [OrderStatus::Cancelled->value]
                : [],
            OrderStatus::Ready->value => $order->order_type === OrderType::Delivery->value
                ? [OrderStatus::OutForDelivery->value]
                : [OrderStatus::Completed->value],
            OrderStatus::OutForDelivery->value => [OrderStatus::Delivered->value],
            OrderStatus::Delivered->value => [OrderStatus::Completed->value],
            default => [],
        };
    }

    public function transition(
        int $branchId,
        int $orderId,
        string $targetStatus,
        User $actor,
        ?string $note = null,
    ): Order {
        return DB::transaction(function () use ($branchId, $orderId, $targetStatus, $actor, $note): Order {
            $order = Order::query()
                ->where('branch_id', $branchId)
                ->lockForUpdate()
                ->findOrFail($orderId);

            if ($order->order_status === $targetStatus) {
                return $order->fresh(['items.modifiers', 'payments', 'statusHistory.changedBy', 'kitchenTickets.station']);
            }

            $allowed = $this->availableTransitions($order);
            if (! in_array($targetStatus, $allowed, true)) {
                throw ValidationException::withMessages([
                    'status' => 'انتقال حالة الطلب غير مسموح من الحالة الحالية.',
                ]);
            }

            $now = now();
            $oldStatus = $order->order_status;
            $updates = ['order_status' => $targetStatus];

            if ($targetStatus === OrderStatus::Confirmed->value) {
                $updates['confirmed_at'] = $order->confirmed_at ?? $now;
            }

            if ($targetStatus === OrderStatus::Completed->value) {
                $updates['completed_at'] = $now;
            }

            if ($targetStatus === OrderStatus::Cancelled->value) {
                $updates['cancelled_at'] = $now;
                $this->cancelQueuedKitchenTickets($order, $actor, $note, $now);
            }

            $order->update($updates);

            OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'old_status' => $oldStatus,
                'new_status' => $targetStatus,
                'changed_by' => $actor->id,
                'source' => 'admin_operations',
                'note' => $note ?: $this->defaultNote($targetStatus),
                'created_at' => $now,
            ]);

            return $order->fresh(['items.modifiers', 'payments', 'statusHistory.changedBy', 'kitchenTickets.station']);
        }, 3);
    }

    private function canCancelBeforePreparation(Order $order): bool
    {
        return ! KitchenTicket::query()
            ->where('order_id', $order->id)
            ->whereIn('status', ['preparing', 'ready'])
            ->exists();
    }

    private function cancelQueuedKitchenTickets(Order $order, User $actor, ?string $note, $now): void
    {
        $tickets = KitchenTicket::query()
            ->where('order_id', $order->id)
            ->lockForUpdate()
            ->get();

        if ($tickets->contains(fn (KitchenTicket $ticket): bool => in_array($ticket->status, ['preparing', 'ready'], true))) {
            throw ValidationException::withMessages([
                'status' => 'لا يمكن إلغاء الطلب بعد بدء التحضير.',
            ]);
        }

        foreach ($tickets as $ticket) {
            if ($ticket->status === 'cancelled') {
                continue;
            }

            $oldTicketStatus = $ticket->status;
            $ticket->update(['status' => 'cancelled']);
            $ticket->items()->update([
                'status' => 'cancelled',
                'updated_at' => $now,
            ]);
            $ticket->statusHistory()->create([
                'old_status' => $oldTicketStatus,
                'new_status' => 'cancelled',
                'changed_by' => $actor->id,
                'source' => 'admin_order_cancellation',
                'note' => $note ?: 'تم إلغاء الطلب من لوحة الإدارة.',
                'created_at' => $now,
            ]);
        }
    }

    private function defaultNote(string $status): string
    {
        return match ($status) {
            OrderStatus::Confirmed->value => 'تم تأكيد الطلب من لوحة الإدارة.',
            OrderStatus::Cancelled->value => 'تم إلغاء الطلب من لوحة الإدارة.',
            OrderStatus::OutForDelivery->value => 'خرج الطلب للتوصيل.',
            OrderStatus::Delivered->value => 'تم تسليم الطلب للعميل.',
            OrderStatus::Completed->value => 'تم إغلاق الطلب.',
            default => 'تم تحديث حالة الطلب.',
        };
    }
}
