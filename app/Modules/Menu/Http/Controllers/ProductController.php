<?php

namespace App\Modules\Menu\Http\Controllers;

use App\Modules\Branches\Models\Branch;
use App\Modules\Menu\Http\Requests\SaveProductRequest;
use App\Modules\Menu\Models\MenuCategory;
use App\Modules\Menu\Models\ModifierGroup;
use App\Modules\Menu\Models\Product;
use App\Modules\Menu\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProductController
{
    public function index(Request $request, Branch $branch): Response
    {
        $products = Product::query()
            ->where('restaurant_id', $branch->restaurant_id)
            ->with([
                'category:id,name',
                'variants' => fn ($query) => $query->with([
                    'branchSettings' => fn ($settings) => $settings->where('branch_id', $branch->id),
                ]),
                'modifierGroups:id,name',
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Menu/Products/Index', [
            'branch' => $branch->only(['id', 'name', 'code']),
            'products' => $products,
            'categories' => MenuCategory::query()
                ->where('restaurant_id', $branch->restaurant_id)
                ->where('is_active', true)
                ->orderBy('sort_order')->orderBy('name')
                ->get(['id', 'name']),
            'modifierGroups' => ModifierGroup::query()
                ->where('restaurant_id', $branch->restaurant_id)
                ->where('is_active', true)
                ->with('options:id,modifier_group_id,name,price_delta,is_active')
                ->orderBy('sort_order')->orderBy('name')
                ->get(['id', 'name', 'selection_type', 'min_selections', 'max_selections']),
            'canManageCatalog' => $request->user()?->can('menu.catalog.manage') ?? false,
        ]);
    }

    public function store(SaveProductRequest $request, Branch $branch): RedirectResponse
    {
        $this->validateReferences($request, $branch);
        $data = $request->validated();
        $this->assertDefaultVariant($data['variants']);
        $this->assertUniqueSkus($data['variants']);

        DB::transaction(function () use ($data, $branch): void {
            $product = Product::create([
                'restaurant_id' => $branch->restaurant_id,
                'category_id' => $data['category_id'] ?? null,
                'name' => $data['name'],
                'translations' => $this->normalizeTranslations($data['translations'] ?? []),
                'slug' => $this->uniqueSlug($branch, $data['slug'] ?? $data['name']),
                'description' => $data['description'] ?? null,
                'sort_order' => $data['sort_order'] ?? 0,
                'preparation_time_minutes' => $data['preparation_time_minutes'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->saveVariants($product, $branch, $data['variants']);
            $this->syncModifierGroups($product, $data['modifier_group_ids'] ?? []);
        });

        return back()->with('success', 'تم إنشاء المنتج وأحجامه بنجاح.');
    }

    public function update(SaveProductRequest $request, Branch $branch, int $product): RedirectResponse
    {
        $model = Product::query()
            ->where('restaurant_id', $branch->restaurant_id)
            ->findOrFail($product);

        $this->validateReferences($request, $branch);
        $data = $request->validated();
        $this->assertDefaultVariant($data['variants']);
        $this->assertVariantOwnership($model, $data['variants']);
        $this->assertUniqueSkus($data['variants']);

        DB::transaction(function () use ($data, $branch, $model): void {
            $model->update([
                'category_id' => $data['category_id'] ?? null,
                'name' => $data['name'],
                'translations' => $this->normalizeTranslations($data['translations'] ?? []),
                'slug' => $this->uniqueSlug($branch, $data['slug'] ?? $data['name'], $model->id),
                'description' => $data['description'] ?? null,
                'sort_order' => $data['sort_order'] ?? 0,
                'preparation_time_minutes' => $data['preparation_time_minutes'] ?? null,
                'is_active' => $data['is_active'] ?? false,
            ]);

            $this->saveVariants($model, $branch, $data['variants']);
            $this->syncModifierGroups($model, $data['modifier_group_ids'] ?? []);
        });

        return back()->with('success', 'تم تحديث المنتج بنجاح.');
    }

    public function destroy(Branch $branch, int $product): RedirectResponse
    {
        $model = Product::query()
            ->where('restaurant_id', $branch->restaurant_id)
            ->findOrFail($product);

        $model->delete();

        return back()->with('success', 'تمت أرشفة المنتج دون حذف سجلاته.');
    }

    private function validateReferences(SaveProductRequest $request, Branch $branch): void
    {
        $data = $request->validated();

        if (! empty($data['category_id']) && ! MenuCategory::query()
            ->where('restaurant_id', $branch->restaurant_id)
            ->whereKey($data['category_id'])
            ->exists()) {
            throw ValidationException::withMessages(['category_id' => 'التصنيف غير تابع لهذا المطعم.']);
        }

        $groupIds = $data['modifier_group_ids'] ?? [];
        if ($groupIds !== []) {
            $validCount = ModifierGroup::query()
                ->where('restaurant_id', $branch->restaurant_id)
                ->whereIn('id', $groupIds)
                ->where('is_active', true)
                ->count();

            if ($validCount !== count(array_unique($groupIds))) {
                throw ValidationException::withMessages(['modifier_group_ids' => 'تحتوي الإضافات على مجموعة غير تابعة لهذا المطعم.']);
            }
        }
    }

    private function assertDefaultVariant(array $variants): void
    {
        $defaultCount = collect($variants)->filter(fn (array $variant) => (bool) ($variant['is_default'] ?? false))->count();

        if ($defaultCount > 1) {
            throw ValidationException::withMessages(['variants' => 'يجب تحديد حجم افتراضي واحد فقط.']);
        }

        if (collect($variants)->every(fn (array $variant) => ! (bool) ($variant['is_active'] ?? true))) {
            throw ValidationException::withMessages(['variants' => 'يجب أن يحتوي المنتج على حجم نشط واحد على الأقل.']);
        }

        if (collect($variants)->contains(fn (array $variant) => (bool) ($variant['is_default'] ?? false) && ! (bool) ($variant['is_active'] ?? true))) {
            throw ValidationException::withMessages(['variants' => 'الحجم الافتراضي يجب أن يكون نشطًا.']);
        }
    }

    private function assertVariantOwnership(Product $product, array $variants): void
    {
        $ids = collect($variants)->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();
        $owned = $product->variants()->whereIn('id', $ids)->count();

        if ($owned !== count(array_unique($ids))) {
            throw ValidationException::withMessages(['variants' => 'أحد أحجام المنتج لا ينتمي إلى هذا المنتج.']);
        }
    }

    private function assertUniqueSkus(array $variants): void
    {
        $submitted = [];

        foreach ($variants as $variant) {
            $sku = trim((string) ($variant['sku'] ?? ''));
            if ($sku === '') {
                continue;
            }

            $normalized = Str::upper($sku);
            if (isset($submitted[$normalized])) {
                throw ValidationException::withMessages(['variants' => "كود SKU مكرر داخل المنتج: {$sku}"]);
            }
            $submitted[$normalized] = true;

            $query = ProductVariant::query()->where('sku', $normalized);
            if (! empty($variant['id'])) {
                $query->where('id', '<>', $variant['id']);
            }

            if ($query->exists()) {
                throw ValidationException::withMessages(['variants' => "كود SKU مستخدم بالفعل: {$sku}"]);
            }
        }
    }

    private function saveVariants(Product $product, Branch $branch, array $variants): void
    {
        // Clear the current default first so PostgreSQL's partial unique index
        // is respected when the default variant changes inside this transaction.
        $product->variants()->update(['is_default' => false]);

        $submittedIds = [];
        foreach (array_values($variants) as $position => $variantData) {
            $variant = ! empty($variantData['id'])
                ? $product->variants()->whereKey($variantData['id'])->firstOrFail()
                : new ProductVariant(['product_id' => $product->id]);

            $variant->fill([
                'name' => $variantData['name'],
                'translations' => $this->normalizeTranslations($variantData['translations'] ?? [], 'name'),
                'sku' => trim((string) ($variantData['sku'] ?? '')) !== '' ? Str::upper(trim((string) $variantData['sku'])) : null,
                'base_price' => $variantData['base_price'],
                'sort_order' => $position,
                'is_default' => (bool) ($variantData['is_default'] ?? false),
                'is_active' => (bool) ($variantData['is_active'] ?? true),
            ]);
            $variant->save();
            $submittedIds[] = $variant->id;

            $variant->branchSettings()->updateOrCreate(
                ['branch_id' => $branch->id],
                [
                    'price_override' => ($variantData['branch_price'] ?? '') !== '' ? $variantData['branch_price'] : null,
                    'is_available' => (bool) ($variantData['branch_is_available'] ?? true),
                ],
            );
        }

        // Variants removed from the submitted form are archived (inactive), not deleted.
        $product->variants()->whereNotIn('id', $submittedIds)->update([
            'is_active' => false,
            'is_default' => false,
        ]);

        // If the editor did not choose one, the first active variant becomes default.
        if (! $product->variants()->where('is_default', true)->exists()) {
            $product->variants()->whereIn('id', $submittedIds)->where('is_active', true)
                ->orderBy('sort_order')->first()?->update(['is_default' => true]);
        }
    }

    private function syncModifierGroups(Product $product, array $groupIds): void
    {
        $sync = [];
        foreach (array_values($groupIds) as $position => $id) {
            $sync[$id] = ['sort_order' => $position];
        }

        $product->modifierGroups()->sync($sync);
    }

    private function normalizeTranslations(array $translations, string $only = 'both'): ?array
    {
        $english = array_filter([
            'name' => $only === 'both' || $only === 'name' ? trim((string) ($translations['en']['name'] ?? '')) : '',
            'description' => $only === 'both' ? trim((string) ($translations['en']['description'] ?? '')) : '',
        ], fn (string $value) => $value !== '');

        return $english === [] ? null : ['en' => $english];
    }

    private function uniqueSlug(Branch $branch, string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'product';
        $slug = $base;
        $suffix = 2;

        while (DB::table('products')
            ->where('restaurant_id', $branch->restaurant_id)
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '<>', $ignoreId))
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
