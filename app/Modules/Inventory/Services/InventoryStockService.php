<?php
namespace App\Modules\Inventory\Services;

use App\Models\User;
use App\Modules\Branches\Models\Branch;
use App\Modules\Inventory\Models\BranchIngredientStock;
use App\Modules\Inventory\Models\Ingredient;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Models\StockReservation;
use App\Modules\Kitchen\Models\KitchenTicket;
use App\Modules\Orders\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryStockService
{
    /** Reserve the recipe requirements for all kitchen ticket items. Products without recipes are not stock-tracked yet. */
    public function reserveForOrder(Order $order): void
    {
        // Freeze the first successful reservation set for this order. Recipes may be
        // edited later, but that must not silently alter quantities already promised
        // to a customer between confirmation and preparation of multiple stations.
        if (StockReservation::query()->where('order_id', $order->id)->whereIn('status', ['reserved', 'consumed'])->exists()) {
            return;
        }

        $order->loadMissing(['kitchenTickets.items.orderItem.modifiers.option.ingredientUsages.ingredient', 'kitchenTickets.items.orderItem.productVariant.recipe.items.ingredient']);
        $requirements = [];
        foreach ($order->kitchenTickets as $ticket) {
            foreach ($ticket->items as $ticketItem) {
                $orderItem = $ticketItem->orderItem;
                if (! $orderItem) { continue; }
                $factor = max(1, (int) $orderItem->quantity);
                $recipe = $orderItem->productVariant?->recipe;
                if ($recipe && $recipe->is_active) {
                    foreach ($recipe->items as $recipeItem) {
                        $qty = $this->quantity((float) $recipeItem->quantity * $factor / max(0.001, (float) $recipe->yield_quantity) * (1 + ((float) $recipeItem->waste_percent / 100)));
                        $this->addRequirement($requirements, $ticketItem, $recipeItem->ingredient_id, $qty);
                    }
                }
                foreach ($orderItem->modifiers as $modifier) {
                    foreach ($modifier->option?->ingredientUsages ?? [] as $usage) {
                        $qty = $this->quantity((float) $usage->quantity_per_option * (int) $modifier->quantity * $factor);
                        $this->addRequirement($requirements, $ticketItem, $usage->ingredient_id, $qty);
                    }
                }
            }
        }
        if ($requirements === []) { return; }
        $requiredIngredientIds = collect($requirements)->pluck('ingredient_id')->unique();
        $ownedIngredients = Ingredient::query()->where('restaurant_id', $order->restaurant_id)->whereIn('id', $requiredIngredientIds)->count();
        if ($ownedIngredients !== $requiredIngredientIds->count()) { throw ValidationException::withMessages(['inventory' => 'تحتوي وصفة الطلب على مكون لا يتبع المطعم.']); }

        DB::transaction(function () use ($order, $requirements): void {
            $ingredientIds = collect($requirements)->pluck('ingredient_id')->unique()->sort()->values();
            $now = now();
            $rows = $ingredientIds->map(fn (int $id): array => [
                'branch_id' => $order->branch_id, 'ingredient_id' => $id,
                'on_hand_qty' => 0, 'reserved_qty' => 0,
                'created_at' => $now, 'updated_at' => $now,
            ])->all();
            DB::table('branch_ingredient_stocks')->insertOrIgnore($rows);
            $stocks = BranchIngredientStock::query()->where('branch_id', $order->branch_id)
                ->whereIn('ingredient_id', $ingredientIds)->orderBy('ingredient_id')->lockForUpdate()->get()->keyBy('ingredient_id');

            $deltas = [];
            $reservationStates = [];
            foreach ($requirements as $requirement) {
                $existing = StockReservation::query()
                    ->where('kitchen_ticket_item_id', $requirement['ticket_item_id'])
                    ->where('ingredient_id', $requirement['ingredient_id'])
                    ->lockForUpdate()->first();
                if ($existing && in_array($existing->status, ['reserved', 'consumed'], true)) {
                    $reservationStates[] = [$existing, $requirement, 0];
                    continue;
                }
                $delta = $requirement['quantity'];
                $deltas[$requirement['ingredient_id']] = ($deltas[$requirement['ingredient_id']] ?? 0) + $delta;
                $reservationStates[] = [$existing, $requirement, $delta];
            }
            foreach ($deltas as $ingredientId => $delta) {
                $stock = $stocks->get($ingredientId);
                $available = (float) $stock->on_hand_qty - (float) $stock->reserved_qty;
                if ($available + 0.0001 < $delta) {
                    $ingredient = Ingredient::query()->find($ingredientId);
                    throw ValidationException::withMessages([
                        'inventory' => 'المخزون غير كافٍ للمكون: '.($ingredient?->name ?? ('#'.$ingredientId)).'. المتاح '.number_format($available, 3).' والمطلوب '.number_format($delta, 3).'.',
                    ]);
                }
            }
            foreach ($deltas as $ingredientId => $delta) {
                $stocks->get($ingredientId)->increment('reserved_qty', $delta);
            }
            foreach ($reservationStates as [$existing, $requirement, $delta]) {
                if ($delta <= 0) { continue; }
                $values = [
                    'branch_id' => $order->branch_id,
                    'order_id' => $order->id,
                    'kitchen_ticket_id' => $requirement['ticket_id'],
                    'kitchen_ticket_item_id' => $requirement['ticket_item_id'],
                    'ingredient_id' => $requirement['ingredient_id'],
                    'reserved_qty' => $delta,
                    'consumed_qty' => 0,
                    'status' => 'reserved',
                    'released_at' => null,
                    'consumed_at' => null,
                ];
                if ($existing) { $existing->update($values); }
                else { StockReservation::query()->create($values); }
            }
        }, 3);
    }

    /** Consume a ticket's reserved stock once when kitchen preparation starts. */
    public function consumeForTicket(KitchenTicket $ticket, ?User $actor = null): void
    {
        DB::transaction(function () use ($ticket, $actor): void {
            $reservations = StockReservation::query()->where('kitchen_ticket_id', $ticket->id)
                ->where('status', 'reserved')->orderBy('ingredient_id')->lockForUpdate()->get();
            if ($reservations->isEmpty()) { return; }
            $stocks = BranchIngredientStock::query()->where('branch_id', $ticket->branch_id)
                ->whereIn('ingredient_id', $reservations->pluck('ingredient_id')->unique())
                ->orderBy('ingredient_id')->lockForUpdate()->get()->keyBy('ingredient_id');
            foreach ($reservations as $reservation) {
                $stock = $stocks->get($reservation->ingredient_id);
                if (
                    ! $stock
                    || (float) $stock->on_hand_qty + 0.0001 < (float) $reservation->reserved_qty
                    || (float) $stock->reserved_qty + 0.0001 < (float) $reservation->reserved_qty
                ) {
                    throw ValidationException::withMessages(['inventory' => 'تعذر خصم مكون محجوز من المخزون؛ رصيد الحجز أو الرصيد الفعلي غير كافٍ.']);
                }
                $qty = (float) $reservation->reserved_qty;
                $stock->update([
                    'on_hand_qty' => $this->quantity((float) $stock->on_hand_qty - $qty),
                    'reserved_qty' => $this->quantity((float) $stock->reserved_qty - $qty),
                ]);
                StockMovement::query()->create([
                    'branch_id' => $ticket->branch_id, 'ingredient_id' => $reservation->ingredient_id,
                    'movement_type' => 'consumption', 'quantity_delta' => -$qty,
                    'source_type' => 'kitchen_ticket', 'source_id' => $ticket->id,
                    'reference' => $ticket->ticket_number, 'note' => 'استهلاك مكونات عند بدء التحضير.',
                    'created_by' => $actor?->id, 'occurred_at' => now(),
                ]);
                $reservation->update(['status' => 'consumed', 'consumed_qty' => $qty, 'consumed_at' => now()]);
            }
        }, 3);
    }

    /** Release reservations on cancellation before the kitchen starts preparing. */
    public function releaseForOrder(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $reservations = StockReservation::query()->where('order_id', $order->id)
                ->where('status', 'reserved')->orderBy('ingredient_id')->lockForUpdate()->get();
            if ($reservations->isEmpty()) { return; }
            $stocks = BranchIngredientStock::query()->where('branch_id', $order->branch_id)
                ->whereIn('ingredient_id', $reservations->pluck('ingredient_id')->unique())
                ->orderBy('ingredient_id')->lockForUpdate()->get()->keyBy('ingredient_id');
            foreach ($reservations as $reservation) {
                $stock = $stocks->get($reservation->ingredient_id);
                if (! $stock || (float) $stock->reserved_qty + 0.0001 < (float) $reservation->reserved_qty) {
                    throw ValidationException::withMessages(['inventory' => 'تعذر تحرير حجز المخزون؛ الرصيد المحجوز غير متطابق.']);
                }
                $stock->decrement('reserved_qty', (float) $reservation->reserved_qty);
                $reservation->update(['status' => 'released', 'released_at' => now()]);
            }
        }, 3);
    }

    public function adjust(Branch $branch, Ingredient $ingredient, float $delta, User $actor, string $reason): BranchIngredientStock
    {
        if (abs($delta) < 0.0005) { throw ValidationException::withMessages(['quantity' => 'يجب إدخال كمية تعديل غير صفرية.']); }
        return DB::transaction(function () use ($branch, $ingredient, $delta, $actor, $reason): BranchIngredientStock {
            abort_unless((int) $ingredient->restaurant_id === (int) $branch->restaurant_id, 404);
            $stock = $this->lockOrCreateStock($branch, $ingredient);
            $newOnHand = $this->quantity((float) $stock->on_hand_qty + $delta);
            if ($newOnHand < (float) $stock->reserved_qty - 0.0001) {
                throw ValidationException::withMessages(['quantity' => 'لا يمكن خفض الرصيد إلى أقل من الكمية المحجوزة.']);
            }
            $stock->update(['on_hand_qty' => $newOnHand]);
            StockMovement::query()->create([
                'branch_id' => $branch->id, 'ingredient_id' => $ingredient->id,
                'movement_type' => 'adjustment', 'quantity_delta' => $delta,
                'source_type' => 'manual_adjustment', 'source_id' => null,
                'reference' => null, 'note' => $reason, 'created_by' => $actor->id, 'occurred_at' => now(),
            ]);
            return $stock->fresh('ingredient.baseUnit');
        }, 3);
    }

    public function lockOrCreateStock(Branch $branch, Ingredient $ingredient): BranchIngredientStock
    {
        $now = now();
        DB::table('branch_ingredient_stocks')->insertOrIgnore([
            'branch_id' => $branch->id, 'ingredient_id' => $ingredient->id,
            'on_hand_qty' => 0, 'reserved_qty' => 0, 'created_at' => $now, 'updated_at' => $now,
        ]);
        return BranchIngredientStock::query()->where('branch_id', $branch->id)
            ->where('ingredient_id', $ingredient->id)->lockForUpdate()->firstOrFail();
    }

    private function addRequirement(array &$requirements, $ticketItem, int $ingredientId, float $quantity): void
    {
        if ($quantity <= 0) { return; }
        $key = $ticketItem->id.':'.$ingredientId;
        if (! isset($requirements[$key])) {
            $requirements[$key] = [
                'ticket_id' => $ticketItem->kitchen_ticket_id,
                'ticket_item_id' => $ticketItem->id,
                'ingredient_id' => $ingredientId,
                'quantity' => 0.0,
            ];
        }
        $requirements[$key]['quantity'] = $this->quantity($requirements[$key]['quantity'] + $quantity);
    }

    private function quantity(float $value): float { return round($value, 3, PHP_ROUND_HALF_UP); }
}
