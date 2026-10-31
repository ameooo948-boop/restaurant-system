<?php

namespace App\Modules\Delivery\Services;

use App\Modules\Branches\Models\Branch;
use App\Modules\Branches\Models\BranchSetting;
use App\Modules\Delivery\Models\DeliveryZone;
use App\Shared\Support\Money;
use Illuminate\Validation\ValidationException;

class DeliveryZoneResolver
{
    /** @return array{zone:?DeliveryZone, fee_minor:int, zone_name:?string} */
    public function resolve(Branch $branch, ?array $address, int $subtotalMinor): array
    {
        $hasConfiguredZones = DeliveryZone::query()
            ->where('branch_id', $branch->id)
            ->where('is_active', true)
            ->exists();

        if (! $hasConfiguredZones) {
            return [
                'zone' => null,
                'fee_minor' => $this->legacyBranchFeeMinor($branch->id),
                'zone_name' => null,
            ];
        }

        $city = trim((string) ($address['city'] ?? ''));
        $area = trim((string) ($address['area'] ?? ''));

        if ($city === '') {
            throw ValidationException::withMessages(['city' => 'المدينة مطلوبة لتحديد نطاق التوصيل.']);
        }

        $zone = DeliveryZone::query()
            ->where('branch_id', $branch->id)
            ->where('is_active', true)
            ->whereRaw('LOWER(TRIM(city)) = LOWER(?)', [$city])
            ->where(function ($query) use ($area): void {
                $query->whereNull('area');
                if ($area !== '') {
                    $query->orWhereRaw('LOWER(TRIM(area)) = LOWER(?)', [$area]);
                }
            })
            ->orderByRaw('CASE WHEN area IS NULL THEN 1 ELSE 0 END')
            ->first();

        if (! $zone) {
            throw ValidationException::withMessages([
                'area' => 'عذرًا، عنوانك خارج نطاق التوصيل لهذا الفرع.',
            ]);
        }

        if ($subtotalMinor < Money::toMinor((string) $zone->minimum_order_amount)) {
            throw ValidationException::withMessages([
                'cart' => 'قيمة الطلب أقل من الحد الأدنى لنطاق التوصيل المحدد.',
            ]);
        }

        return [
            'zone' => $zone,
            'fee_minor' => Money::toMinor((string) $zone->delivery_fee),
            'zone_name' => $zone->name,
        ];
    }

    private function legacyBranchFeeMinor(int $branchId): int
    {
        $setting = BranchSetting::query()
            ->where('branch_id', $branchId)
            ->where('setting_key', 'delivery_fee')
            ->first();

        $value = $setting?->setting_value;
        if (is_array($value)) {
            $value = $value['amount'] ?? '0.00';
        }

        return Money::toMinor(is_numeric($value) ? (string) $value : '0.00');
    }
}
