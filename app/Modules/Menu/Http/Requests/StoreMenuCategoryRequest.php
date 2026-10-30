<?php

namespace App\Modules\Menu\Http\Requests;

use App\Modules\Branches\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMenuCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $branch = $this->route('branch');
        $categoryId = $this->route('category');

        return [
            'parent_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:120'],
            'translations' => ['nullable', 'array'],
            'translations.en.name' => ['nullable', 'string', 'max:120'],
            'translations.en.description' => ['nullable', 'string', 'max:5000'],
            'slug' => [
                'nullable', 'string', 'max:150',
                Rule::unique('menu_categories', 'slug')
                    ->where('restaurant_id', $branch instanceof Branch ? $branch->restaurant_id : null)
                    ->ignore($categoryId),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['nullable', 'integer', 'between:-32768,32767'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
