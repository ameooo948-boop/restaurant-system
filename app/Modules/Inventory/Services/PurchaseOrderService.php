<?php
namespace App\Modules\Inventory\Services;

use App\Models\User;
use App\Modules\Branches\Models\Branch;
use App\Modules\Inventory\Models\GoodsReceipt;
use App\Modules\Inventory\Models\PurchaseOrder;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PurchaseOrderService
{
    public function create(Branch $branch, Supplier $supplier, array $items, User $actor, ?string $notes = null): PurchaseOrder
    {
        abort_unless((int) $supplier->restaurant_id === (int) $branch->restaurant_id && $supplier->is_active, 404);
        if ($items === []) { throw ValidationException::withMessages(['items' => 'أضف صنفًا واحدًا على الأقل لأمر الشراء.']); }

        return DB::transaction(function () use ($branch, $supplier, $items, $actor, $notes): PurchaseOrder {
            Branch::query()->whereKey($branch->id)->lockForUpdate()->firstOrFail();
            $ingredientIds = collect($items)->pluck('ingredient_id')->map(fn ($id) => (int) $id)->unique();
            $owned = \App\Modules\Inventory\Models\Ingredient::query()
                ->where('restaurant_id', $branch->restaurant_id)->whereIn('id', $ingredientIds)->count();
            if ($owned !== $ingredientIds->count()) {
                throw ValidationException::withMessages(['items' => 'يوجد مكون لا يتبع هذا المطعم.']);
            }
            $number = 'PO-'.$branch->code.'-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));
            $order = PurchaseOrder::query()->create([
                'public_id' => (string) Str::uuid(), 'branch_id' => $branch->id,
                'supplier_id' => $supplier->id, 'purchase_number' => $number,
                'status' => 'ordered', 'currency' => $branch->restaurant->currency ?? 'EGP',
                'total_amount' => 0, 'notes' => $notes, 'created_by' => $actor->id, 'ordered_at' => now(),
            ]);
            $total = 0.0;
            foreach ($items as $item) {
                $qty = round((float) $item['quantity_ordered'], 3);
                $cost = round((float) $item['unit_cost'], 4);
                $total += $qty * $cost;
                $order->items()->create([
                    'ingredient_id' => (int) $item['ingredient_id'],
                    'quantity_ordered' => $qty, 'quantity_received' => 0, 'unit_cost' => $cost,
                ]);
            }
            $order->update(['total_amount' => round($total, 2)]);
            return $order->load(['supplier', 'items.ingredient.baseUnit']);
        }, 3);
    }

    /** @param array<int, float> $receivedByPurchaseItemId */
    public function receive(PurchaseOrder $purchaseOrder, array $receivedByPurchaseItemId, User $actor, string $idempotencyKey, ?string $notes = null): GoodsReceipt
    {
        return DB::transaction(function () use ($purchaseOrder, $receivedByPurchaseItemId, $actor, $idempotencyKey, $notes): GoodsReceipt {
            $order = PurchaseOrder::query()->whereKey($purchaseOrder->id)->lockForUpdate()->firstOrFail();
            $existing = GoodsReceipt::query()->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                if ((int) $existing->purchase_order_id !== (int) $order->id) {
                    throw ValidationException::withMessages(['idempotency_key' => 'مفتاح الاستلام مستخدم لأمر شراء آخر.']);
                }
                return $existing->load('items');
            }
            if (in_array($order->status, ['received', 'cancelled'], true)) {
                throw ValidationException::withMessages(['purchase_order' => 'أمر الشراء مغلق ولا يقبل استلامًا جديدًا.']);
            }
            if ($receivedByPurchaseItemId === []) {
                throw ValidationException::withMessages(['items' => 'أدخل كميات الاستلام.']);
            }
            $purchaseItems = $order->items()->whereIn('id', array_map('intval', array_keys($receivedByPurchaseItemId)))
                ->orderBy('ingredient_id')->lockForUpdate()->get()->keyBy('id');
            if ($purchaseItems->count() !== count($receivedByPurchaseItemId)) {
                throw ValidationException::withMessages(['items' => 'يوجد صنف لا يتبع أمر الشراء المحدد.']);
            }
            $receipt = GoodsReceipt::query()->create([
                'public_id' => (string) Str::uuid(), 'idempotency_key' => $idempotencyKey,
                'purchase_order_id' => $order->id, 'branch_id' => $order->branch_id,
                'receipt_number' => 'GR-'.$order->id.'-'.now()->format('YmdHis').'-'.Str::upper(Str::random(3)),
                'received_by' => $actor->id, 'received_at' => now(), 'notes' => $notes,
            ]);
            // Process purchase items in the same ingredient order used for row locks.
            // This keeps stock-row lock ordering deterministic across concurrent receipts.
            foreach ($purchaseItems as $item) {
                $rawQty = $receivedByPurchaseItemId[$item->id];
                $qty = round((float) $rawQty, 3);
                if ($qty <= 0 || (float) $item->quantity_received + $qty > (float) $item->quantity_ordered + 0.0001) {
                    throw ValidationException::withMessages(['items' => 'كمية الاستلام غير صالحة أو تتجاوز الكمية المتبقية.']);
                }
                $item->increment('quantity_received', $qty);
                $receipt->items()->create([
                    'purchase_order_item_id' => $item->id, 'ingredient_id' => $item->ingredient_id,
                    'received_qty' => $qty, 'unit_cost' => $item->unit_cost,
                ]);
                $stock = app(InventoryStockService::class)->lockOrCreateStock($order->branch, $item->ingredient);
                $stock->increment('on_hand_qty', $qty);
                StockMovement::query()->create([
                    'branch_id' => $order->branch_id, 'ingredient_id' => $item->ingredient_id,
                    'movement_type' => 'purchase_receipt', 'quantity_delta' => $qty,
                    'unit_cost' => $item->unit_cost, 'source_type' => 'goods_receipt', 'source_id' => $receipt->id,
                    'reference' => $receipt->receipt_number, 'note' => 'استلام مشتريات.',
                    'created_by' => $actor->id, 'occurred_at' => now(),
                ]);
            }
            $order->refresh()->load('items');
            $allReceived = $order->items->every(fn ($item): bool => (float) $item->quantity_received + 0.0001 >= (float) $item->quantity_ordered);
            $anyReceived = $order->items->contains(fn ($item): bool => (float) $item->quantity_received > 0);
            $order->update([
                'status' => $allReceived ? 'received' : ($anyReceived ? 'partially_received' : 'ordered'),
                'received_at' => $allReceived ? now() : null,
            ]);
            return $receipt->load('items');
        }, 3);
    }
}
