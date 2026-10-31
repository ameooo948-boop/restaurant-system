<?php

namespace App\Modules\Cart\Services;

use App\Modules\Branches\Models\Branch;
use App\Modules\Cart\Models\Cart;
use App\Modules\Cart\Models\CartItem;
use App\Modules\Menu\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function __construct(
        private readonly CartManager $manager,
        private readonly CartPricingService $pricing,
    ) {
    }

    public function addItem(Branch $branch, Request $request, array $data): Cart
    {
        // Validate the catalog selection before creating a cart for invalid product IDs.
        $resolved = $this->pricing->validateAndResolve(
            $branch,
            ProductVariant::query()->findOrFail($data['product_variant_id']),
            $data['modifiers'] ?? [],
        );
        $cart = $this->manager->resolve($branch, $request);

        DB::transaction(function () use ($cart, $data, $resolved): void {
            $locked = Cart::query()->whereKey($cart->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'active' || ($locked->expires_at && $locked->expires_at->isPast())) {
                throw ValidationException::withMessages(['cart' => 'انتهت صلاحية السلة. حدّث الصفحة وابدأ سلة جديدة.']);
            }

            $item = $locked->items()->create([
                'product_variant_id' => $resolved['variant']->id,
                'quantity' => $data['quantity'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($resolved['modifiers'] as $modifier) {
                $item->modifiers()->create([
                    'modifier_option_id' => $modifier['option']->id,
                    'quantity' => $modifier['quantity'],
                ]);
            }
        }, 3);

        return $cart->fresh(['items.modifiers']);
    }

    public function updateItem(Cart $cart, CartItem $item, array $data): void
    {
        DB::transaction(function () use ($cart, $item, $data): void {
            $lockedCart = Cart::query()->whereKey($cart->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedCart->status === 'active', 409);
            $lockedItem = $lockedCart->items()->whereKey($item->id)->lockForUpdate()->firstOrFail();
            $lockedItem->update([
                'quantity' => $data['quantity'],
                'notes' => $data['notes'] ?? null,
            ]);
        }, 3);
    }

    public function removeItem(Cart $cart, CartItem $item): void
    {
        DB::transaction(function () use ($cart, $item): void {
            $lockedCart = Cart::query()->whereKey($cart->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedCart->status === 'active', 409);
            $lockedCart->items()->whereKey($item->id)->lockForUpdate()->firstOrFail()->delete();
        }, 3);
    }
}
