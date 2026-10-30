<?php

namespace App\Modules\Menu\Http\Controllers;

use App\Modules\Branches\Models\Branch;
use App\Modules\Menu\Http\Requests\StoreMenuCategoryRequest;
use App\Modules\Menu\Models\MenuCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MenuCategoryController
{
    public function index(Request $request, Branch $branch): Response
    {
        $categories = MenuCategory::query()
            ->where('restaurant_id', $branch->restaurant_id)
            ->with(['parent:id,name'])
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('Menu/Categories/Index', [
            'branch' => $branch->only(['id', 'name', 'code']),
            'categories' => $categories,
            'canManageCatalog' => $request->user()?->can('menu.catalog.manage') ?? false,
        ]);
    }

    public function store(StoreMenuCategoryRequest $request, Branch $branch): RedirectResponse
    {
        $data = $request->validated();
        $this->assertParentBelongsToRestaurant($data['parent_id'] ?? null, $branch);

        MenuCategory::create([
            'restaurant_id' => $branch->restaurant_id,
            'parent_id' => $data['parent_id'] ?? null,
            'name' => $data['name'],
            'translations' => $this->normalizeTranslations($data['translations'] ?? []),
            'slug' => $this->uniqueSlug($branch, $data['slug'] ?? $data['name']),
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return back()->with('success', 'تم إنشاء التصنيف بنجاح.');
    }

    public function update(StoreMenuCategoryRequest $request, Branch $branch, int $category): RedirectResponse
    {
        $model = $this->findCategory($branch, $category);
        $data = $request->validated();
        $parentId = $data['parent_id'] ?? null;

        if ((int) $parentId === $model->id) {
            throw ValidationException::withMessages(['parent_id' => 'لا يمكن أن يكون التصنيف أبًا لنفسه.']);
        }

        $this->assertParentBelongsToRestaurant($parentId, $branch);
        $this->assertParentIsNotDescendant($model, $parentId);

        $model->update([
            'parent_id' => $parentId,
            'name' => $data['name'],
            'translations' => $this->normalizeTranslations($data['translations'] ?? []),
            'slug' => $this->uniqueSlug($branch, $data['slug'] ?? $data['name'], $model->id),
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $data['is_active'] ?? false,
        ]);

        return back()->with('success', 'تم تحديث التصنيف بنجاح.');
    }

    public function destroy(Branch $branch, int $category): RedirectResponse
    {
        $model = $this->findCategory($branch, $category);

        if ($model->products()->exists() || $model->children()->exists()) {
            throw ValidationException::withMessages([
                'category' => 'لا يمكن أرشفة تصنيف يحتوي على منتجات أو تصنيفات فرعية. انقل العناصر أولاً.',
            ]);
        }

        $model->delete();

        return back()->with('success', 'تمت أرشفة التصنيف.');
    }

    private function findCategory(Branch $branch, int $id): MenuCategory
    {
        return MenuCategory::query()
            ->where('restaurant_id', $branch->restaurant_id)
            ->findOrFail($id);
    }

    private function assertParentBelongsToRestaurant(?int $parentId, Branch $branch): void
    {
        if ($parentId === null) {
            return;
        }

        $exists = MenuCategory::query()
            ->where('restaurant_id', $branch->restaurant_id)
            ->whereKey($parentId)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages(['parent_id' => 'التصنيف الأب غير صالح لهذا المطعم.']);
        }
    }

    private function assertParentIsNotDescendant(MenuCategory $category, ?int $parentId): void
    {
        $visited = [];
        while ($parentId !== null) {
            if ($parentId === $category->id || in_array($parentId, $visited, true)) {
                throw ValidationException::withMessages(['parent_id' => 'اختيار هذا التصنيف الأب سيؤدي إلى تكوين دورة في التصنيفات.']);
            }

            $visited[] = (int) $parentId;
            $parentId = MenuCategory::query()->whereKey($parentId)->value('parent_id');
            $parentId = $parentId === null ? null : (int) $parentId;
        }
    }

    private function normalizeTranslations(array $translations): ?array
    {
        $english = array_filter([
            'name' => trim((string) ($translations['en']['name'] ?? '')),
            'description' => trim((string) ($translations['en']['description'] ?? '')),
        ], fn (string $value) => $value !== '');

        return $english === [] ? null : ['en' => $english];
    }

    private function uniqueSlug(Branch $branch, string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'category';
        $slug = $base;
        $suffix = 2;

        while (DB::table('menu_categories')
            ->where('restaurant_id', $branch->restaurant_id)
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '<>', $ignoreId))
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
