<?php

namespace App\Modules\Kitchen\Http\Controllers;

use App\Modules\Branches\Models\Branch;
use App\Modules\Kitchen\Http\Requests\UpdateKitchenTicketStatusRequest;
use App\Modules\Kitchen\Models\KitchenTicket;
use App\Modules\Kitchen\Services\KitchenTicketService;
use Illuminate\Http\RedirectResponse;

class KitchenTicketController
{
    public function updateStatus(
        Branch $branch,
        int $ticket,
        UpdateKitchenTicketStatusRequest $request,
        KitchenTicketService $service,
    ): RedirectResponse {
        abort_unless(
            KitchenTicket::query()->where('branch_id', $branch->id)->whereKey($ticket)->exists(),
            404,
        );

        $service->transition(
            $branch->id,
            $ticket,
            $request->validated('status'),
            $request->user(),
            $request->validated('note'),
        );

        return back()->with('success', 'تم تحديث تذكرة المطبخ.');
    }
}
