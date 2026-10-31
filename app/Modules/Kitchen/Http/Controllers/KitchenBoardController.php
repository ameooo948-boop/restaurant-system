<?php

namespace App\Modules\Kitchen\Http\Controllers;

use App\Modules\Branches\Models\Branch;
use App\Modules\Kitchen\Models\KitchenStation;
use App\Modules\Kitchen\Models\KitchenTicket;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KitchenBoardController
{
    public function __invoke(Request $request, Branch $branch): Response
    {
        $stations = KitchenStation::query()
            ->where('branch_id', $branch->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'is_default']);

        $selectedStationId = $request->integer('station_id') ?: null;
        if ($selectedStationId !== null && ! $stations->contains('id', $selectedStationId)) {
            abort(404);
        }

        $tickets = KitchenTicket::query()
            ->where('branch_id', $branch->id)
            ->whereIn('status', ['queued', 'preparing'])
            ->when($selectedStationId, fn ($query) => $query->where('kitchen_station_id', $selectedStationId))
            ->with([
                'station:id,code,name',
                'order:id,order_number,order_type,order_status,customer_name,table_label,customer_notes,placed_at',
                'items:id,kitchen_ticket_id,order_item_id,status,product_name_snapshot,variant_name_snapshot,quantity,modifiers_snapshot,notes_snapshot,started_at,ready_at',
            ])
            ->orderByRaw("CASE WHEN status = 'preparing' THEN 0 ELSE 1 END")
            ->orderBy('queued_at')
            ->get()
            ->map(fn (KitchenTicket $ticket): array => [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'status' => $ticket->status,
                'queued_at' => $ticket->queued_at?->toIso8601String(),
                'started_at' => $ticket->started_at?->toIso8601String(),
                'station' => $ticket->station->only(['id', 'code', 'name']),
                'order' => $ticket->order->only([
                    'id', 'order_number', 'order_type', 'order_status', 'customer_name', 'table_label', 'customer_notes', 'placed_at',
                ]),
                'items' => $ticket->items->map(fn ($item): array => [
                    'id' => $item->id,
                    'status' => $item->status,
                    'product_name' => $item->product_name_snapshot,
                    'variant_name' => $item->variant_name_snapshot,
                    'quantity' => $item->quantity,
                    'modifiers' => $item->modifiers_snapshot ?? [],
                    'notes' => $item->notes_snapshot,
                ])->values(),
            ])->values();

        $summary = [
            'queued' => KitchenTicket::query()->where('branch_id', $branch->id)->where('status', 'queued')->count(),
            'preparing' => KitchenTicket::query()->where('branch_id', $branch->id)->where('status', 'preparing')->count(),
            'ready_last_hour' => KitchenTicket::query()->where('branch_id', $branch->id)->where('status', 'ready')->where('ready_at', '>=', now()->subHour())->count(),
        ];

        return Inertia::render('Kitchen/Board', [
            'branch' => $branch->only(['id', 'code', 'name']),
            'stations' => $stations,
            'tickets' => $tickets,
            'summary' => $summary,
            'selectedStationId' => $selectedStationId,
            'canManageStations' => $request->user()->can('kitchen.stations.manage'),
            'canUpdateTickets' => $request->user()->can('kitchen.tickets.update'),
        ]);
    }
}
