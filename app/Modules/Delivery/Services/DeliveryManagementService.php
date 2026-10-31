<?php

namespace App\Modules\Delivery\Services;

use App\Models\User;
use App\Modules\Delivery\Enums\DeliveryStatus;
use App\Modules\Delivery\Models\Delivery;
use App\Modules\Delivery\Models\DeliveryStatusHistory;
use App\Modules\Orders\Services\OrderManagementService;
use App\Modules\Payments\Actions\RecordCashPaymentAction;
use App\Modules\Staff\Models\BranchUserAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

class DeliveryManagementService
{
    public function __construct(
        private readonly OrderManagementService $orders,
        private readonly RecordCashPaymentAction $cashPayments,
    ) {}

    public function assignDriver(int $branchId, int $deliveryId, int $driverUserId, User $actor): Delivery
    {
        return DB::transaction(function () use ($branchId, $deliveryId, $driverUserId, $actor): Delivery {
            $delivery = Delivery::query()->where('branch_id', $branchId)->lockForUpdate()->findOrFail($deliveryId);
            if (! in_array($delivery->status, [DeliveryStatus::PendingAssignment->value, DeliveryStatus::Failed->value, DeliveryStatus::Assigned->value], true)) {
                throw ValidationException::withMessages(['driver_user_id' => 'لا يمكن تغيير المندوب بعد استلام الطلب للتوصيل.']);
            }

            $assignment = BranchUserAssignment::query()
                ->where('branch_id', $branchId)
                ->where('user_id', $driverUserId)
                ->where('is_active', true)
                ->with('user')
                ->first();

            if (! $assignment?->user || ! $this->hasDeliveryPermission($assignment->user, $branchId)) {
                throw ValidationException::withMessages(['driver_user_id' => 'يجب أن يكون المندوب موظف توصيل نشطًا في هذا الفرع.']);
            }

            $oldStatus = $delivery->status;
            $delivery->update([
                'driver_user_id' => $driverUserId,
                'status' => DeliveryStatus::Assigned->value,
                'assigned_at' => now(),
                'failed_at' => null,
                'failure_reason' => null,
            ]);
            $this->history($delivery, $oldStatus, DeliveryStatus::Assigned->value, $actor, 'تم تعيين مندوب للطلب.');

            return $delivery->fresh(['order', 'driver', 'zone']);
        }, 3);
    }

    public function updateStatus(int $branchId, int $deliveryId, string $target, User $actor, ?string $note = null, ?string $failureReason = null): Delivery
    {
        return DB::transaction(function () use ($branchId, $deliveryId, $target, $actor, $note, $failureReason): Delivery {
            $delivery = Delivery::query()->where('branch_id', $branchId)->lockForUpdate()->findOrFail($deliveryId);
            $oldStatus = $delivery->status;
            $actorIsDriver = (int) $delivery->driver_user_id === (int) $actor->id;

            if ($target === DeliveryStatus::PickedUp->value) {
                if ($oldStatus !== DeliveryStatus::Assigned->value || ! $actorIsDriver) {
                    throw ValidationException::withMessages(['status' => 'فقط المندوب المعين يمكنه استلام الطلب من المطعم.']);
                }
                $delivery->update(['status' => $target, 'picked_up_at' => now()]);
                $this->history($delivery, $oldStatus, $target, $actor, $note ?: 'استلم المندوب الطلب.');
                $this->orders->transition($branchId, $delivery->order_id, 'out_for_delivery', $actor, 'استلم المندوب الطلب وخرج للتوصيل.');
            } elseif ($target === DeliveryStatus::Delivered->value) {
                if ($oldStatus !== DeliveryStatus::PickedUp->value || ! $actorIsDriver) {
                    throw ValidationException::withMessages(['status' => 'لا يمكن إتمام التسليم إلا بواسطة المندوب المعين بعد استلام الطلب.']);
                }
                $delivery->update(['status' => $target, 'delivered_at' => now()]);
                $this->history($delivery, $oldStatus, $target, $actor, $note ?: 'تم تسليم الطلب للعميل.');
                $order = $this->orders->transition($branchId, $delivery->order_id, 'delivered', $actor, 'أكد المندوب تسليم الطلب.');
                if ($order->payment_method === 'cash' && $order->payment_status !== 'succeeded') {
                    $this->cashPayments->execute($branchId, $order->id, $actor, 'تم التحصيل عند التسليم بواسطة المندوب.', 'delivery');
                }
            } elseif ($target === DeliveryStatus::Failed->value) {
                if ($oldStatus !== DeliveryStatus::PickedUp->value || ! $actorIsDriver) {
                    throw ValidationException::withMessages(['status' => 'لا يمكن تسجيل تعذر التوصيل إلا بواسطة المندوب المعين.']);
                }
                $delivery->update([
                    'status' => $target,
                    'failed_at' => now(),
                    'failure_reason' => $failureReason,
                ]);
                $this->history($delivery, $oldStatus, $target, $actor, $note ?: $failureReason);
            } else {
                throw ValidationException::withMessages(['status' => 'حالة التوصيل المطلوبة غير مدعومة.']);
            }

            return $delivery->fresh(['order', 'driver', 'zone', 'statusHistory']);
        }, 3);
    }

    private function hasDeliveryPermission(User $user, int $branchId): bool
    {
        $registrar = app(PermissionRegistrar::class);
        $previousTeam = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId($branchId);
        try {
            return $user->can('deliveries.update');
        } finally {
            $registrar->setPermissionsTeamId($previousTeam);
        }
    }

    private function history(Delivery $delivery, ?string $old, string $new, User $actor, ?string $note): void
    {
        DeliveryStatusHistory::query()->create([
            'delivery_id' => $delivery->id,
            'old_status' => $old,
            'new_status' => $new,
            'changed_by' => $actor->id,
            'note' => $note,
        ]);
    }
}
