<?php

namespace App\Modules\Kitchen\Http\Controllers;

use App\Modules\Branches\Models\Branch;
use App\Modules\Kitchen\Http\Requests\SaveKitchenStationRequest;
use App\Modules\Kitchen\Http\Requests\SyncKitchenStationProductsRequest;
use App\Modules\Kitchen\Models\KitchenStation;
use App\Modules\Kitchen\Models\KitchenStationProduct;
use App\Modules\Menu\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class KitchenStationController
{
    public function index(Branch $branch): Response
    {
        $stations = KitchenStation::query()
            ->where('branch_id', $branch->id)
            ->with('products:id,name')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (KitchenStation $station): array => [
                'id' => $station->id,
                'code' => $station->code,
                'name' => $station->name,
                'description' => $station->description,
                'sort_order' => $station->sort_order,
                'is_default' => $station->is_default,
                'is_active' => $station->is_active,
                'product_ids' => $station->products->pluck('id')->map(fn ($id) => (int) $id)->values(),
            ]);

        $products = Product::query()
            ->where('restaurant_id', $branch->restaurant_id)
            ->where('is_active', true)
            ->with('category:id,name')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'category_id', 'name']);

        return Inertia::render('Kitchen/Stations', [
            'branch' => $branch->only(['id', 'code', 'name']),
            'stations' => $stations,
            'products' => $products->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category?->name,
            ]),
        ]);
    }

    public function store(Branch $branch, SaveKitchenStationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $branch->kitchenStations()->create([
            ...$data,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
            'is_default' => false,
        ]);

        return back()->with('success', 'تم إنشاء محطة التحضير.');
    }

    public function update(Branch $branch, int $station, SaveKitchenStationRequest $request): RedirectResponse
    {
        $model = $branch->kitchenStations()->findOrFail($station);
        $data = $request->validated();

        if ($model->is_default && ($data['code'] !== 'GENERAL' || ! ($data['is_active'] ?? true))) {
            throw ValidationException::withMessages(['code' => 'لا يمكن تغيير كود أو تعطيل محطة المطبخ العامة.']);
        }

        $model->update($data);

        return back()->with('success', 'تم تحديث محطة التحضير.');
    }

    public function syncProducts(Branch $branch, int $station, SyncKitchenStationProductsRequest $request): RedirectResponse
    {
        $model = $branch->kitchenStations()->findOrFail($station);
        if (! $model->is_active) {
            throw ValidationException::withMessages(['station' => 'فعّل المحطة قبل توزيع المنتجات عليها.']);
        }

        $productIds = collect($request->validated('product_ids'))->map(fn ($id) => (int) $id)->all();
        $ownedCount = Product::query()
            ->where('restaurant_id', $branch->restaurant_id)
            ->whereIn('id', $productIds)
            ->count();

        if ($ownedCount !== count($productIds)) {
            throw ValidationException::withMessages([
                'product_ids' => 'تحتوي القائمة على منتج غير تابع لهذا المطعم.',
            ]);
        }

        DB::transaction(function () use ($branch, $model, $productIds): void {
            Branch::query()->whereKey($branch->id)->lockForUpdate()->firstOrFail();

            KitchenStationProduct::query()
                ->where('branch_id', $branch->id)
                ->where('kitchen_station_id', $model->id)
                ->delete();

            if ($productIds === []) {
                return;
            }

            // One product can be routed to only one station within a branch.
            KitchenStationProduct::query()
                ->where('branch_id', $branch->id)
                ->whereIn('product_id', $productIds)
                ->delete();

            foreach ($productIds as $productId) {
                KitchenStationProduct::query()->create([
                    'branch_id' => $branch->id,
                    'kitchen_station_id' => $model->id,
                    'product_id' => $productId,
                ]);
            }
        });

        return back()->with('success', 'تم حفظ توزيع المنتجات على محطة التحضير.');
    }
}
