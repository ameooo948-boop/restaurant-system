<?php

namespace App\Modules\Reports\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

class ReportFiltersRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) return;

            $timezone = (string) config('app.timezone', 'UTC');
            $today = CarbonImmutable::now($timezone)->toDateString();
            $from = $this->input('from') ?: CarbonImmutable::parse($today, $timezone)->subDays(29)->toDateString();
            $to = $this->input('to') ?: $today;

            try {
                $start = CarbonImmutable::parse($from, $timezone)->startOfDay();
                $end = CarbonImmutable::parse($to, $timezone)->startOfDay();
            } catch (\Throwable) {
                return;
            }

            if ($start->greaterThan($end)) {
                $validator->errors()->add('from', 'تاريخ البداية يجب أن يسبق تاريخ النهاية أو يساويه.');
            } elseif ($start->diffInDays($end) > 365) {
                $validator->errors()->add('to', 'الفترة القصوى للتقرير 366 يومًا.');
            }
        });
    }
}
