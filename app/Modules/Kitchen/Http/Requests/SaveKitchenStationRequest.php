<?php

namespace App\Modules\Kitchen\Http\Requests;

use App\Modules\Branches\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveKitchenStationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $branch = $this->route('branch');
        $station = $this->route('station');
        $stationId = is_object($station) ? $station->id : $station;
        $branchId = $branch instanceof Branch ? $branch->id : (int) $branch;

        return [
            'code' => [
                'required', 'string', 'max:30', 'regex:/^[A-Z0-9_]+$/',
                Rule::unique('kitchen_stations', 'code')->where('branch_id', $branchId)->ignore($stationId),
            ],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:32767'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
