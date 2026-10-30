<?php

namespace App\Modules\Menu\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:160'],
            'translations' => ['nullable', 'array'],
            'translations.en.name' => ['nullable', 'string', 'max:160'],
            'translations.en.description' => ['nullable', 'string', 'max:10000'],
            'slug' => ['nullable', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:10000'],
            'sort_order' => ['nullable', 'integer', 'between:-32768,32767'],
            'preparation_time_minutes' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['sometimes', 'boolean'],
            'variants' => ['required', 'array', 'min:1', 'max:30'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.name' => ['required', 'string', 'max:100'],
            'variants.*.translations' => ['nullable', 'array'],
            'variants.*.translations.en.name' => ['nullable', 'string', 'max:100'],
            'variants.*.sku' => ['nullable', 'string', 'max:100'],
            'variants.*.base_price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'variants.*.is_default' => ['sometimes', 'boolean'],
            'variants.*.is_active' => ['sometimes', 'boolean'],
            'variants.*.branch_price' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'variants.*.branch_is_available' => ['sometimes', 'boolean'],
            'modifier_group_ids' => ['sometimes', 'array', 'max:30'],
            'modifier_group_ids.*' => ['integer', 'distinct'],
        ];
    }
}
