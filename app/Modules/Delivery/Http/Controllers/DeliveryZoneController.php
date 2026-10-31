<?php

namespace App\Modules\Delivery\Http\Controllers;

use App\Modules\Branches\Models\Branch;
use App\Modules\Delivery\Http\Requests\SaveDeliveryZoneRequest;
use App\Modules\Delivery\Models\DeliveryZone;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DeliveryZoneController
{
    public function index(Branch $branch): Response
    {
        return Inertia::render('Admin/DeliveryZones/Index', [
            'branch' => $branch->only(['id', 'code', 'name']),
            'zones' => DeliveryZone::query()->where('branch_id', $branch->id)->orderBy('city')->orderBy('area')->get(),
        ]);
    }

    public function store(Branch $branch, SaveDeliveryZoneRequest $request): RedirectResponse
    {
        DeliveryZone::query()->create(array_merge($request->validated(), ['branch_id' => $branch->id]));
        return back()->with('success', 'تمت إضافة نطاق التوصيل.');
    }

    public function update(Branch $branch, int $zone, SaveDeliveryZoneRequest $request): RedirectResponse
    {
        $record = DeliveryZone::query()->where('branch_id', $branch->id)->findOrFail($zone);
        $record->update($request->validated());
        return back()->with('success', 'تم تحديث نطاق التوصيل.');
    }

    public function destroy(Branch $branch, int $zone): RedirectResponse
    {
        $record = DeliveryZone::query()->where('branch_id', $branch->id)->findOrFail($zone);
        $record->delete();
        return back()->with('success', 'تم حذف نطاق التوصيل.');
    }
}
