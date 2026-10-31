<?php

namespace App\Modules\Payments\Actions;

use App\Models\User;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordCashPaymentAction
{
    public function execute(
        int $branchId,
        int $orderId,
        User $actor,
        ?string $note = null,
        string $channel = 'admin',
    ): Payment {
        return DB::transaction(function () use ($branchId, $orderId, $actor, $note, $channel): Payment {
            $order = Order::query()
                ->where('branch_id', $branchId)
                ->lockForUpdate()
                ->findOrFail($orderId);

            if ($order->payment_method !== 'cash') {
                throw ValidationException::withMessages(['payment' => 'تسجيل التحصيل النقدي متاح للطلبات النقدية فقط.']);
            }

            $payment = $order->payments()
                ->where('method', 'cash')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                throw ValidationException::withMessages(['payment' => 'لا يوجد سجل دفع نقدي لهذا الطلب.']);
            }

            if ($payment->status === 'succeeded' || $order->payment_status === 'succeeded') {
                return $payment;
            }

            if ($payment->status !== 'pending' || in_array($order->order_status, ['cancelled'], true)) {
                throw ValidationException::withMessages(['payment' => 'لا يمكن تسجيل تحصيل هذا الطلب في حالته الحالية.']);
            }

            $metadata = $payment->metadata ?? [];
            $metadata['cash_collection'] = [
                'channel' => $channel,
                'recorded_by' => $actor->id,
                'recorded_at' => now()->toIso8601String(),
                'note' => $note,
            ];

            $payment->update([
                'status' => 'succeeded',
                'paid_at' => now(),
                'metadata' => $metadata,
            ]);
            $order->update(['payment_status' => 'succeeded']);

            return $payment->fresh();
        }, 3);
    }
}
