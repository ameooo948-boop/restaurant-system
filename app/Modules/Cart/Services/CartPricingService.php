<?php

namespace App\Modules\Cart\Services;

use App\Modules\Branches\Models\Branch;
use App\Modules\Cart\Models\Cart;
use App\Modules\Cart\Models\CartItem;
use App\Modules\Menu\Models\BranchModifierPrice;
use App\Modules\Menu\Models\BranchProductSetting;
use App\Modules\Menu\Models\ModifierOption;
use App\Modules\Menu\Models\ProductVariant;
use App\Shared\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CartPricingService
{
    /** @param array<int, array{modifier_option_id:int, quantity:int}> $selections */
    public function validateAndResolve(Branch $branch, ProductVariant $variant, array $selections): array
    {
        $variant->loadMissing(['product.modifierGroups.options']);
        $product = $variant->product;

        if (! $variant->is_active || ! $product || ! $product->is_active || (int) $product->restaurant_id !== (int) $branch->restaurant_id) {
            throw ValidationException::withMessages(['product_variant_id' => 'هذا المنتج غير متاح في هذا الفرع.']);
        }

        $setting = BranchProductSetting::query()
            ->where('branch_id', $branch->id)
            ->where('product_variant_id', $variant->id)
            ->first();

        if ($setting && ! $this->isAvailableAtBranch($setting, $branch)) {
            throw ValidationException::withMessages(['product_variant_id' => 'هذا الحجم غير متاح حاليًا في هذا الفرع أو خارج موعد توفره.']);
        }

        $basePrice = $setting?->price_override !== null ? $setting->price_override : $variant->base_price;
        $baseMinor = Money::toMinor((string) $basePrice);
        $groups = $product->modifierGroups()->where('modifier_groups.is_active', true)
            ->with(['options' => fn ($query) => $query->where('is_active', true)])
            ->get();

        $normalized = collect($selections)->keyBy(fn (array $selection) => (int) $selection['modifier_option_id']);
        if ($normalized->count() !== count($selections)) {
            throw ValidationException::withMessages(['modifiers' => 'لا يمكن تكرار الاختيار نفسه أكثر من مرة؛ استخدم الكمية.']);
        }

        $allOptions = $groups->flatMap->options->keyBy('id');
        if ($normalized->keys()->diff($allOptions->keys())->isNotEmpty()) {
            throw ValidationException::withMessages(['modifiers' => 'أحد الاختيارات لا ينتمي إلى هذا المنتج.']);
        }

        $selectedByGroup = [];
        foreach ($normalized as $optionId => $selection) {
            $option = $allOptions->get((int) $optionId);
            $selectedByGroup[$option->modifier_group_id][] = [
                'option' => $option,
                'quantity' => (int) $selection['quantity'],
            ];
        }

        $modifierSnapshots = [];
        $unitMinor = $baseMinor;

        foreach ($groups as $group) {
            $groupSelections = $selectedByGroup[$group->id] ?? [];
            $selectedCount = array_sum(array_column($groupSelections, 'quantity'));
            $max = $group->max_selections === null
                ? $group->options->count()
                : (int) $group->max_selections;

            if ($selectedCount < (int) $group->min_selections || $selectedCount > $max) {
                throw ValidationException::withMessages([
                    'modifiers' => "اختيارات مجموعة {$group->name} لا تطابق الحد الأدنى أو الأقصى.",
                ]);
            }

            if ($group->selection_type === 'single' && $selectedCount > 1) {
                throw ValidationException::withMessages(['modifiers' => "مجموعة {$group->name} تسمح باختيار واحد فقط."]);
            }

            foreach ($groupSelections as $entry) {
                /** @var ModifierOption $option */
                $option = $entry['option'];
                $branchPrice = BranchModifierPrice::query()
                    ->where('branch_id', $branch->id)
                    ->where('modifier_option_id', $option->id)
                    ->first();

                if ($branchPrice && ! $branchPrice->is_available) {
                    throw ValidationException::withMessages(['modifiers' => "الاختيار {$option->name} غير متاح في هذا الفرع."]);
                }

                $delta = $branchPrice?->price_delta_override !== null
                    ? $branchPrice->price_delta_override
                    : $option->price_delta;
                $deltaMinor = Money::toMinor((string) $delta);
                $quantity = $entry['quantity'];
                $unitMinor += $deltaMinor * $quantity;
                $modifierSnapshots[] = [
                    'option' => $option,
                    'group_name' => $group->name,
                    'quantity' => $quantity,
                    'unit_price_delta_minor' => $deltaMinor,
                ];
            }
        }

        if ($unitMinor < 0) {
            throw ValidationException::withMessages(['modifiers' => 'لا يمكن أن يصبح سعر الوحدة بعد الإضافات أقل من صفر.']);
        }

        return [
            'product' => $product,
            'variant' => $variant,
            'unit_price_minor' => $unitMinor,
            'modifiers' => $modifierSnapshots,
        ];
    }

    private function isAvailableAtBranch(BranchProductSetting $setting, Branch $branch): bool
    {
        if (! $setting->is_available) {
            return false;
        }

        $from = $setting->available_from;
        $until = $setting->available_until;
        if (! $from && ! $until) {
            return true;
        }

        $timezone = $branch->restaurant?->timezone ?: config('restaurant.timezone', 'Africa/Cairo');
        $current = now($timezone)->format('H:i:s');
        $from = $from ? substr((string) $from, 0, 8) : null;
        $until = $until ? substr((string) $until, 0, 8) : null;

        if ($from && $until) {
            // Supports both same-day windows and overnight windows, e.g. 20:00-02:00.
            return strcmp($from, $until) <= 0
                ? ($current >= $from && $current <= $until)
                : ($current >= $from || $current <= $until);
        }

        return $from ? $current >= $from : $current <= $until;
    }

    public function summarize(Cart $cart): array
    {
        $cart->loadMissing(['branch.restaurant', 'items.variant.product', 'items.modifiers.option.group']);
        $branch = $cart->branch;
        $items = [];
        $subtotalMinor = 0;

        foreach ($cart->items as $item) {
            $selections = $item->modifiers->map(fn ($modifier) => [
                'modifier_option_id' => (int) $modifier->modifier_option_id,
                'quantity' => (int) $modifier->quantity,
            ])->all();

            $resolved = $this->validateAndResolve($branch, $item->variant, $selections);
            $quantity = (int) $item->quantity;
            $lineMinor = $resolved['unit_price_minor'] * $quantity;
            $subtotalMinor += $lineMinor;
            $items[] = [
                'id' => $item->id,
                'product_id' => $resolved['product']->id,
                'product_name' => $resolved['product']->name,
                'variant_id' => $resolved['variant']->id,
                'variant_name' => $resolved['variant']->name,
                'quantity' => $quantity,
                'notes' => $item->notes,
                'unit_price' => Money::fromMinor($resolved['unit_price_minor']),
                'line_total' => Money::fromMinor($lineMinor),
                'modifiers' => array_map(fn (array $modifier) => [
                    'option_id' => $modifier['option']->id,
                    'name' => $modifier['option']->name,
                    'group_name' => $modifier['group_name'],
                    'quantity' => $modifier['quantity'],
                    'unit_price_delta' => Money::fromMinor($modifier['unit_price_delta_minor']),
                ], $resolved['modifiers']),
            ];
        }

        return [
            'id' => $cart->public_id,
            'currency' => $cart->currency,
            'items' => $items,
            'items_count' => array_sum(array_column($items, 'quantity')),
            'subtotal' => Money::fromMinor($subtotalMinor),
            'delivery_fee' => '0.00',
            'total' => Money::fromMinor($subtotalMinor),
        ];
    }
}
