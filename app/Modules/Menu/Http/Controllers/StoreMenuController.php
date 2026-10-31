<?php

namespace App\Modules\Menu\Http\Controllers;

use App\Modules\Branches\Models\Branch;
use App\Modules\Menu\Models\MenuCategory;
use App\Modules\Menu\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class StoreMenuController
{
    public function index(Branch $branch): Response
    {
        $categories = MenuCategory::query()
            ->where('restaurant_id', $branch->restaurant_id)
            ->where('is_active', true)
            ->orderBy('sort_order')->orderBy('name')
            ->get(['id', 'name', 'translations']);

        $products = Product::query()
            ->where('restaurant_id', $branch->restaurant_id)
            ->where('is_active', true)
            ->with([
                'category:id,name',
                'variants' => fn ($query) => $query->where('is_active', true)->with([
                    'branchSettings' => fn ($settings) => $settings->where('branch_id', $branch->id),
                ]),
                'modifierGroups' => fn ($groups) => $groups->where('modifier_groups.is_active', true)->with([
                    'options' => fn ($options) => $options->where('is_active', true)->with([
                        'branchPrices' => fn ($prices) => $prices->where('branch_id', $branch->id),
                    ]),
                ]),
            ])
            ->orderBy('sort_order')->orderBy('name')
            ->limit(100)
            ->get();

        return Inertia::render('Store/Menu/Index', [
            'branch' => $branch->only(['id', 'name', 'code']),
            'categories' => $categories,
            'products' => $products->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'image_path' => $product->image_path,
                'category' => $product->category?->only(['id', 'name']),
                'variants' => $product->variants->map(function ($variant) use ($branch) {
                    $setting = $variant->branchSettings->first();
                    $available = $setting?->is_available ?? true;
                    $price = $setting?->price_override !== null ? $setting->price_override : $variant->base_price;
                    return [
                        'id' => $variant->id,
                        'name' => $variant->name,
                        'price' => $price,
                        'is_default' => $variant->is_default,
                        'is_available' => $available,
                    ];
                })->values(),
                'modifier_groups' => $product->modifierGroups->map(fn ($group) => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'selection_type' => $group->selection_type,
                    'min_selections' => $group->min_selections,
                    'max_selections' => $group->max_selections,
                    'options' => $group->options->map(function ($option) {
                        $branchPrice = $option->branchPrices->first();
                        return [
                            'id' => $option->id,
                            'name' => $option->name,
                            'price_delta' => $branchPrice?->price_delta_override !== null
                                ? $branchPrice->price_delta_override
                                : $option->price_delta,
                            'is_available' => $branchPrice?->is_available ?? true,
                        ];
                    })->values(),
                ])->values(),
            ])->values(),
            'cartUrl' => route('store.cart.show', $branch),
        ]);
    }
}
