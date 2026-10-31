<?php

namespace App\Modules\Delivery\Http\Controllers;

use App\Modules\Branches\Models\Branch;
use App\Modules\Delivery\Http\Requests\AssignDeliveryDriverRequest;
use App\Modules\Delivery\Http\Requests\UpdateDeliveryStatusRequest;
use App\Modules\Delivery\Models\Delivery;
use App\Modules\Delivery\Services\DeliveryManagementService;
use App\Modules\Staff\Models\BranchUserAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\PermissionRegistrar;

class DeliveryController
{
    public function index(Branch $branch, Request $request, PermissionRegistrar $registrar): Response
    {
        $isDispatcher = $request->user()->can('deliveries.assign');
        $drivers = collect();
        if ($isDispatcher) {
            $previousTeam = $registrar->getPermissionsTeamId();
            $registrar->setPermissionsTeamId($branch->id);
            try {
                $drivers = BranchUserAssignment::query()
                    ->where('branch_id', $branch->id)
                    ->where('is_active', true)
                    ->with('user:id,name')
                    ->get()
                    ->filter(fn ($assignment) => $assignment->user && $assignment->user->can('deliveries.update'))
                    ->map(fn ($assignment) => [
                        'id' => $assignment->user->id,
                        'name' => $assignment->user->name,
                    ])->values();
            } finally {
                $registrar->setPermissionsTeamId($previousTeam);
            }
        }

        $deliveryQuery = Delivery::query()->where('branch_id', $branch->id);
        if (! $isDispatcher) {
            $deliveryQuery->where('driver_user_id', $request->user()->id);
            $drivers = collect();
        }

        $deliveries = $deliveryQuery
            ->with(['order:id,order_number,customer_name,customer_phone,order_status,order_type,total,currency,delivery_address_snapshot', 'driver:id,name', 'zone:id,name'])
            ->latest('id')->paginate(20)->through(fn (Delivery $delivery): array => [
                'id' => $delivery->id,
                'status' => $delivery->status,
                'driver' => $delivery->driver?->only(['id', 'name']),
                'zone' => $delivery->zone?->name,
                'order' => $delivery->order?->only(['id', 'order_number', 'customer_name', 'customer_phone', 'order_status', 'order_type', 'total', 'currency', 'delivery_address_snapshot']),
                'assigned_at' => $delivery->assigned_at?->toIso8601String(),
                'picked_up_at' => $delivery->picked_up_at?->toIso8601String(),
            ]);

        return Inertia::render('Admin/Deliveries/Index', [
            'branch' => $branch->only(['id', 'code', 'name']),
            'deliveries' => $deliveries,
            'drivers' => $drivers,
        ]);
    }

    public function assign(Branch $branch, int $delivery, AssignDeliveryDriverRequest $request, DeliveryManagementService $service): RedirectResponse
    {
        $service->assignDriver($branch->id, $delivery, (int) $request->validated('driver_user_id'), $request->user());
        return back()->with('success', 'تم تعيين مندوب التوصيل.');
    }

    public function updateStatus(Branch $branch, int $delivery, UpdateDeliveryStatusRequest $request, DeliveryManagementService $service): RedirectResponse
    {
        $data = $request->validated();
        $service->updateStatus(
            $branch->id,
            $delivery,
            $data['status'],
            $request->user(),
            $data['note'] ?? null,
            $data['failure_reason'] ?? null,
        );
        return back()->with('success', 'تم تحديث حالة التوصيل.');
    }
}
