<?php

namespace App\Modules\Kitchen\Actions;

use App\Modules\Branches\Models\Branch;
use App\Modules\Kitchen\Models\KitchenStation;
use App\Modules\Kitchen\Models\KitchenStationProduct;
use App\Modules\Kitchen\Models\KitchenTicket;
use App\Modules\Orders\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateKitchenTicketsAction
{
    public function execute(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $branch = Branch::query()->whereKey($order->branch_id)->lockForUpdate()->firstOrFail();
            $order->loadMissing('items.modifiers');

            if ($order->items->isEmpty()) {
                return;
            }

            $groups = [];

            foreach ($order->items as $item) {
                $station = null;

                if ($item->product_id !== null) {
                    $station = KitchenStationProduct::query()
                        ->where('branch_id', $branch->id)
                        ->where('product_id', $item->product_id)
                        ->whereHas('station', fn ($query) => $query->where('is_active', true))
                        ->with('station')
                        ->first()?->station;
                }

                $station ??= $this->defaultStation($branch);
                $groups[$station->id]['station'] = $station;
                $groups[$station->id]['items'][] = $item;
            }

            foreach ($groups as $group) {
                /** @var KitchenStation $station */
                $station = $group['station'];
                $ticketNumber = ($order->order_number ?: 'ORD-'.$order->id).'-'.$station->code;

                $ticket = KitchenTicket::query()->firstOrCreate(
                    [
                        'order_id' => $order->id,
                        'kitchen_station_id' => $station->id,
                    ],
                    [
                        'public_id' => (string) Str::uuid(),
                        'branch_id' => $branch->id,
                        'ticket_number' => $ticketNumber,
                        'status' => 'queued',
                        'queued_at' => $order->placed_at ?? now(),
                    ],
                );

                foreach ($group['items'] as $orderItem) {
                    $modifiers = $orderItem->modifiers->map(fn ($modifier): array => [
                        'group' => $modifier->group_name_snapshot,
                        'name' => $modifier->option_name_snapshot,
                        'quantity' => (int) $modifier->quantity,
                    ])->values()->all();

                    $ticket->items()->firstOrCreate(
                        ['order_item_id' => $orderItem->id],
                        [
                            'status' => 'queued',
                            'product_name_snapshot' => $orderItem->product_name_snapshot,
                            'variant_name_snapshot' => $orderItem->variant_name_snapshot,
                            'quantity' => $orderItem->quantity,
                            'modifiers_snapshot' => $modifiers,
                            'notes_snapshot' => $orderItem->notes,
                        ],
                    );
                }
            }
        }, 3);
    }

    public function ensureDefaultStation(Branch $branch): KitchenStation
    {
        $station = KitchenStation::query()->firstOrCreate(
            ['branch_id' => $branch->id, 'code' => 'GENERAL'],
            [
                'name' => 'المطبخ العام',
                'description' => 'محطة احتياطية للعناصر غير الموزعة على محطة أخرى.',
                'sort_order' => 0,
                'is_default' => true,
                'is_active' => true,
            ],
        );

        if (! $station->is_active) {
            $station->update(['is_active' => true]);
        }

        return $station;
    }

    private function defaultStation(Branch $branch): KitchenStation
    {
        return $this->ensureDefaultStation($branch);
    }
}
