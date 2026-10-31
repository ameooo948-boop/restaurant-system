<?php

namespace App\Modules\Cart\Http\Controllers;

use App\Modules\Branches\Models\Branch;
use App\Modules\Cart\Http\Requests\AddCartItemRequest;
use App\Modules\Cart\Http\Requests\UpdateCartItemRequest;
use App\Modules\Cart\Models\CartItem;
use App\Modules\Cart\Services\CartManager;
use App\Modules\Cart\Services\CartPricingService;
use App\Modules\Cart\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StoreCartController
{
    public function show(Branch $branch, Request $request, CartManager $manager, CartPricingService $pricing): Response
    {
        $cart = $manager->resolve($branch, $request);

        return Inertia::render('Store/Cart/Show', [
            'branch' => $branch->only(['id', 'name', 'code']),
            'cart' => $pricing->summarize($cart),
            'checkoutUrl' => route('store.checkout.create', $branch),
        ]);
    }

    public function store(Branch $branch, AddCartItemRequest $request, CartService $service): RedirectResponse
    {
        $service->addItem($branch, $request, $request->validated());

        return redirect()->route('store.cart.show', $branch)->with('success', 'تمت إضافة المنتج إلى السلة.');
    }

    public function update(Branch $branch, int $cartItem, UpdateCartItemRequest $request, CartManager $manager, CartService $service): RedirectResponse
    {
        $cart = $manager->resolve($branch, $request);
        $manager->assertOwnedByRequest($cart, $request);
        $item = CartItem::query()->where('cart_id', $cart->id)->findOrFail($cartItem);
        $service->updateItem($cart, $item, $request->validated());

        return back()->with('success', 'تم تحديث السلة.');
    }

    public function destroy(Branch $branch, int $cartItem, Request $request, CartManager $manager, CartService $service): RedirectResponse
    {
        $cart = $manager->resolve($branch, $request);
        $manager->assertOwnedByRequest($cart, $request);
        $item = CartItem::query()->where('cart_id', $cart->id)->findOrFail($cartItem);
        $service->removeItem($cart, $item);

        return back()->with('success', 'تم حذف المنتج من السلة.');
    }
}
