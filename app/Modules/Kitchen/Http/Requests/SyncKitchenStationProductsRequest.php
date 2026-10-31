<?php

namespace App\Modules\Kitchen\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncKitchenStationProductsRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'product_ids' => ['present', 'array', 'max:500'],
            'product_ids.*' => ['required', 'integer', 'distinct'],
        ];
    }
}
