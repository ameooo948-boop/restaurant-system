<?php
namespace App\Modules\Inventory\Services;

use App\Models\User;
use App\Modules\Branches\Models\Branch;
use App\Modules\Inventory\Models\BranchIngredientStock;
use App\Modules\Inventory\Models\Ingredient;
use App\Modules\Inventory\Models\InventoryCount;
use App\Modules\Inventory\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InventoryCountService
{
    public function createDraft(Branch $branch, User $actor, ?string $notes = null): InventoryCount
    {
        return DB::transaction(function () use ($branch, $actor, $notes): InventoryCount {
            $ingredients = Ingredient::query()->where('restaurant_id', $branch->restaurant_id)->where('is_active', true)->orderBy('id')->get();
            $count = InventoryCount::query()->create([
                'public_id' => (string) Str::uuid(), 'branch_id' => $branch->id,
                'status' => 'draft', 'counted_by' => $actor->id, 'counted_at' => now(), 'notes' => $notes,
            ]);
            $stockService = app(InventoryStockService::class);
            foreach ($ingredients as $ingredient) {
                $stock = $stockService->lockOrCreateStock($branch, $ingredient);
                $count->items()->create([
                    'ingredient_id' => $ingredient->id, 'expected_qty' => $stock->on_hand_qty,
                ]);
            }
            return $count->load('items.ingredient.baseUnit');
        }, 3);
    }

    public function updateCountedQuantity(InventoryCount $count, int $itemId, float $quantity, ?string $note = null)
    {
        if ($quantity < 0) { throw ValidationException::withMessages(['counted_qty' => 'الكمية الفعلية لا يمكن أن تكون سالبة.']); }
        return DB::transaction(function () use ($count, $itemId, $quantity, $note) {
            $locked = InventoryCount::query()->whereKey($count->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'draft') { throw ValidationException::withMessages(['count' => 'الجرد غير قابل للتعديل بعد الترحيل.']); }
            $item = $locked->items()->whereKey($itemId)->lockForUpdate()->firstOrFail();
            $item->update(['counted_qty' => round($quantity, 3), 'note' => $note]);
            return $item->fresh('ingredient.baseUnit');
        }, 3);
    }

    public function post(InventoryCount $inventoryCount, User $actor): InventoryCount
    {
        return DB::transaction(function () use ($inventoryCount, $actor): InventoryCount {
            $count = InventoryCount::query()->whereKey($inventoryCount->id)->lockForUpdate()->firstOrFail();
            if ($count->status === 'posted') { return $count->load('items.ingredient'); }
            if ($count->status !== 'draft') { throw ValidationException::withMessages(['count' => 'حالة الجرد لا تسمح بالترحيل.']); }
            $items = $count->items()->orderBy('ingredient_id')->lockForUpdate()->get();
            if ($items->isEmpty() || $items->contains(fn ($item): bool => $item->counted_qty === null)) {
                throw ValidationException::withMessages(['counted_qty' => 'أدخل الكمية الفعلية لكل المكونات قبل ترحيل الجرد.']);
            }
            $stocks = BranchIngredientStock::query()->where('branch_id', $count->branch_id)
                ->whereIn('ingredient_id', $items->pluck('ingredient_id'))->orderBy('ingredient_id')->lockForUpdate()->get()->keyBy('ingredient_id');
            foreach ($items as $item) {
                $stock = $stocks->get($item->ingredient_id);
                if (! $stock) { throw ValidationException::withMessages(['count' => 'رصيد المكون غير موجود.']); }
                $systemQty = (float) $stock->on_hand_qty;
                $countedQty = (float) $item->counted_qty;
                if ($countedQty + 0.0001 < (float) $stock->reserved_qty) {
                    throw ValidationException::withMessages(['counted_qty' => 'الكمية الفعلية لا تغطي الكميات المحجوزة لهذا المكون.']);
                }
                $variance = round($countedQty - $systemQty, 3);
                $item->update(['expected_qty' => $systemQty, 'variance_qty' => $variance]);
                if (abs($variance) >= 0.0005) {
                    $stock->update(['on_hand_qty' => $countedQty]);
                    StockMovement::query()->create([
                        'branch_id' => $count->branch_id, 'ingredient_id' => $item->ingredient_id,
                        'movement_type' => 'count_adjustment', 'quantity_delta' => $variance,
                        'source_type' => 'inventory_count', 'source_id' => $count->id,
                        'reference' => $count->public_id, 'note' => 'تسوية ناتجة عن الجرد الفعلي.',
                        'created_by' => $actor->id, 'occurred_at' => now(),
                    ]);
                }
            }
            $count->update(['status' => 'posted', 'posted_by' => $actor->id, 'posted_at' => now()]);
            return $count->fresh('items.ingredient');
        }, 3);
    }
}
