<?php

namespace App\Modules\Menu\Http\Controllers;

use App\Modules\Branches\Models\Branch;
use App\Modules\Menu\Http\Requests\SaveModifierGroupRequest;
use App\Modules\Menu\Models\ModifierGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ModifierGroupController
{
    public function index(Request $request, Branch $branch): Response
    {
        return Inertia::render('Menu/Modifiers/Index', [
            'branch' => $branch->only(['id', 'name', 'code']),
            'groups' => ModifierGroup::query()
                ->where('restaurant_id', $branch->restaurant_id)
                ->with(['options' => fn ($query) => $query->with([
                    'branchPrices' => fn ($prices) => $prices->where('branch_id', $branch->id),
                ])])
                ->withCount('products')
                ->orderBy('sort_order')->orderBy('name')
                ->get(),
            'canManageCatalog' => $request->user()?->can('menu.catalog.manage') ?? false,
        ]);
    }

    public function store(SaveModifierGroupRequest $request, Branch $branch): RedirectResponse
    {
        $data = $request->validated();
        $this->assertSelectionBounds($data);
        $this->assertOptionsOwnership(null, $data['options']);

        DB::transaction(function () use ($data, $branch): void {
            $group = ModifierGroup::create([
                'restaurant_id' => $branch->restaurant_id,
                'name' => $data['name'],
                'translations' => $this->normalizeTranslations($data['translations'] ?? [], true),
                'description' => $data['description'] ?? null,
                'selection_type' => $data['selection_type'],
                'min_selections' => $data['min_selections'],
                'max_selections' => $this->normalizedMax($data),
                'sort_order' => $data['sort_order'] ?? 0,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->saveOptions($group, $branch, $data['options']);
        });

        return back()->with('success', 'تم إنشاء مجموعة الإضافات بنجاح.');
    }

    public function update(SaveModifierGroupRequest $request, Branch $branch, int $group): RedirectResponse
    {
        $model = ModifierGroup::query()
            ->where('restaurant_id', $branch->restaurant_id)
            ->findOrFail($group);

        $data = $request->validated();
        $this->assertSelectionBounds($data);
        $this->assertOptionsOwnership($model, $data['options']);

        DB::transaction(function () use ($data, $branch, $model): void {
            $model->update([
                'name' => $data['name'],
                'translations' => $this->normalizeTranslations($data['translations'] ?? [], true),
                'description' => $data['description'] ?? null,
                'selection_type' => $data['selection_type'],
                'min_selections' => $data['min_selections'],
                'max_selections' => $this->normalizedMax($data),
                'sort_order' => $data['sort_order'] ?? 0,
                'is_active' => $data['is_active'] ?? false,
            ]);

            $this->saveOptions($model, $branch, $data['options']);
        });

        return back()->with('success', 'تم تحديث مجموعة الإضافات.');
    }

    public function destroy(Branch $branch, int $group): RedirectResponse
    {
        $model = ModifierGroup::query()
            ->where('restaurant_id', $branch->restaurant_id)
            ->findOrFail($group);

        if ($model->products()->exists()) {
            throw ValidationException::withMessages([
                'group' => 'لا يمكن أرشفة مجموعة مرتبطة بمنتجات. افصلها عن المنتجات أولاً.',
            ]);
        }

        $model->delete();

        return back()->with('success', 'تمت أرشفة مجموعة الإضافات.');
    }

    private function assertSelectionBounds(array $data): void
    {
        $min = (int) $data['min_selections'];
        $max = $this->normalizedMax($data);

        if ($max !== null && $min > $max) {
            throw ValidationException::withMessages(['min_selections' => 'الحد الأدنى لا يمكن أن يتجاوز الحد الأقصى.']);
        }

        if ($data['selection_type'] === 'single' && ($max === null || $max > 1 || $min > 1)) {
            throw ValidationException::withMessages(['max_selections' => 'مجموعة الاختيار الفردي تسمح باختيار واحد كحد أقصى.']);
        }

        $activeOptions = collect($data['options'])->filter(fn (array $option) => (bool) ($option['is_active'] ?? true))->count();
        if (($data['is_active'] ?? true) && $activeOptions === 0) {
            throw ValidationException::withMessages(['options' => 'يجب أن تحتوي المجموعة النشطة على اختيار نشط واحد على الأقل.']);
        }

        if ($min > $activeOptions) {
            throw ValidationException::withMessages(['min_selections' => 'الحد الأدنى أكبر من عدد الاختيارات النشطة.']);
        }
    }

    private function normalizedMax(array $data): ?int
    {
        if ($data['selection_type'] === 'single') {
            return 1;
        }

        return isset($data['max_selections']) ? (int) $data['max_selections'] : null;
    }

    private function assertOptionsOwnership(?ModifierGroup $group, array $options): void
    {
        $ids = collect($options)->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();
        if ($ids === []) {
            return;
        }

        if ($group === null) {
            throw ValidationException::withMessages(['options' => 'لا يمكن إرسال معرف خيار عند إنشاء مجموعة جديدة.']);
        }

        $count = $group->options()->whereIn('id', $ids)->count();
        if ($count !== count(array_unique($ids))) {
            throw ValidationException::withMessages(['options' => 'أحد الخيارات لا ينتمي إلى مجموعة الإضافات الحالية.']);
        }
    }

    private function normalizeTranslations(array $translations, bool $includeDescription): ?array
    {
        $english = array_filter([
            'name' => trim((string) ($translations['en']['name'] ?? '')),
            'description' => $includeDescription ? trim((string) ($translations['en']['description'] ?? '')) : '',
        ], fn (string $value) => $value !== '');

        return $english === [] ? null : ['en' => $english];
    }

    private function saveOptions(ModifierGroup $group, Branch $branch, array $options): void
    {
        $submittedIds = [];

        foreach (array_values($options) as $position => $optionData) {
            $option = ! empty($optionData['id'])
                ? $group->options()->whereKey($optionData['id'])->firstOrFail()
                : $group->options()->make();

            $option->fill([
                'name' => $optionData['name'],
                'translations' => $this->normalizeTranslations($optionData['translations'] ?? [], false),
                'price_delta' => $optionData['price_delta'],
                'sort_order' => $position,
                'is_active' => (bool) ($optionData['is_active'] ?? true),
            ]);
            $option->save();
            $submittedIds[] = $option->id;

            $option->branchPrices()->updateOrCreate(
                ['branch_id' => $branch->id],
                [
                    'price_delta_override' => ($optionData['branch_price_delta'] ?? '') !== '' ? $optionData['branch_price_delta'] : null,
                    'is_available' => (bool) ($optionData['branch_is_available'] ?? true),
                ],
            );
        }

        $group->options()->whereNotIn('id', $submittedIds)->update(['is_active' => false]);
    }
}
