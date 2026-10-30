<?php

namespace App\Modules\Menu\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveModifierGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'translations' => ['nullable', 'array'],
            'translations.en.name' => ['nullable', 'string', 'max:120'],
            'translations.en.description' => ['nullable', 'string', 'max:5000'],
            'description' => ['nullable', 'string', 'max:5000'],
            'selection_type' => ['required', Rule::in(['single', 'multiple'])],
            'min_selections' => ['required', 'integer', 'min:0', 'max:30'],
            'max_selections' => ['nullable', 'integer', 'min:1', 'max:30'],
            'sort_order' => ['nullable', 'integer', 'between:-32768,32767'],
            'is_active' => ['sometimes', 'boolean'],
            'options' => ['required', 'array', 'min:1', 'max:50'],
            'options.*.id' => ['nullable', 'integer', 'distinct'],
            'options.*.name' => ['required', 'string', 'max:120', 'distinct'],
            'options.*.translations' => ['nullable', 'array'],
            'options.*.translations.en.name' => ['nullable', 'string', 'max:120'],
            'options.*.price_delta' => ['required', 'numeric', 'between:-9999999999.99,9999999999.99'],
            'options.*.branch_price_delta' => ['nullable', 'numeric', 'between:-9999999999.99,9999999999.99'],
            'options.*.is_active' => ['sometimes', 'boolean'],
            'options.*.branch_is_available' => ['sometimes', 'boolean'],
        ];
    }
}
