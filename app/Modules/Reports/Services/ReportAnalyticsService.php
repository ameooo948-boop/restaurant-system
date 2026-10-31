<?php

namespace App\Modules\Reports\Services;

use App\Modules\Branches\Models\Branch;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

class ReportAnalyticsService
{
    private const FULFILLED_STATUSES = ['delivered', 'completed'];
    private const REFUND_MARKED_STATUSES = ['refunded', 'partially_refunded'];

    public function build(Branch $branch, string $from, string $to): array
    {
        $orders = $this->ordersInPeriod($branch, $from, $to);
        $fulfilled = $this->fulfilledOrders($branch, $from, $to);

        $totalOrders = (clone $orders)->count();
        $cancelledOrders = (clone $orders)->where('order_status', 'cancelled')->count();
        $refundMarkedOrders = (clone $fulfilled)->whereIn('payment_status', self::REFUND_MARKED_STATUSES)->count();
        $recognized = (clone $fulfilled)->whereNotIn('payment_status', self::REFUND_MARKED_STATUSES);

        $sales = (clone $recognized)->selectRaw(
            'COUNT(*) AS orders_count, COALESCE(SUM(subtotal), 0) AS subtotal, COALESCE(SUM(discount_total), 0) AS discounts, COALESCE(SUM(tax_total), 0) AS taxes, COALESCE(SUM(delivery_fee), 0) AS delivery_fees, COALESCE(SUM(total), 0) AS net_sales'
        )->first();

        $paidQuery = Payment::query()
            ->where('status', 'succeeded')
            ->whereNotNull('paid_at')
            ->whereHas('order', fn (EloquentBuilder $query) => $query->where('branch_id', $branch->id))
            ->whereRaw($this->dateExpression('paid_at', $branch).' BETWEEN ? AND ?', [$from, $to]);
        $collected = (float) (clone $paidQuery)->sum('amount');
        $paymentMix = (clone $paidQuery)
            ->select('method')
            ->selectRaw('COUNT(*) AS payments_count, COALESCE(SUM(amount), 0) AS amount')
            ->groupBy('method')->orderByDesc('amount')->get()
            ->map(fn ($row) => [
                'method' => $row->method,
                'label' => $this->paymentMethodLabel($row->method),
                'count' => (int) $row->payments_count,
                'amount' => (float) $row->amount,
            ])->values()->all();

        $unpaid = (clone $recognized)
            ->whereIn('payment_status', ['pending', 'failed'])
            ->selectRaw('COUNT(*) AS orders_count, COALESCE(SUM(total), 0) AS amount')->first();

        $dailySales = (clone $recognized)
            ->selectRaw($this->dateExpression('placed_at', $branch).' AS report_date, COUNT(*) AS orders_count, COALESCE(SUM(total), 0) AS sales_total')
            ->groupByRaw($this->dateExpression('placed_at', $branch))
            ->orderByRaw($this->dateExpression('placed_at', $branch))
            ->get()->map(fn ($row) => [
                'date' => (string) $row->report_date,
                'orders' => (int) $row->orders_count,
                'sales' => (float) $row->sales_total,
            ])->values()->all();

        $ordersByType = (clone $recognized)
            ->select('order_type')
            ->selectRaw('COUNT(*) AS orders_count, COALESCE(SUM(total), 0) AS sales_total')
            ->groupBy('order_type')->orderByDesc('orders_count')->get()
            ->map(fn ($row) => [
                'type' => $row->order_type,
                'label' => $this->orderTypeLabel($row->order_type),
                'count' => (int) $row->orders_count,
                'sales' => (float) $row->sales_total,
            ])->values()->all();

        $topProducts = DB::table('order_items AS oi')
            ->join('orders AS o', 'o.id', '=', 'oi.order_id')
            ->where('o.branch_id', $branch->id)
            ->whereIn('o.order_status', self::FULFILLED_STATUSES)
            ->whereNotIn('o.payment_status', self::REFUND_MARKED_STATUSES)
            ->whereRaw($this->dateExpression('o.placed_at', $branch).' BETWEEN ? AND ?', [$from, $to])
            ->select('oi.product_name_snapshot', 'oi.variant_name_snapshot')
            ->selectRaw('COALESCE(SUM(oi.quantity), 0) AS quantity_sold, COALESCE(SUM(oi.line_subtotal), 0) AS sales_total')
            ->groupBy('oi.product_name_snapshot', 'oi.variant_name_snapshot')
            ->orderByDesc('quantity_sold')->limit(10)->get()
            ->map(fn ($row) => [
                'product' => $row->product_name_snapshot,
                'variant' => $row->variant_name_snapshot,
                'quantity' => (int) $row->quantity_sold,
                'sales' => (float) $row->sales_total,
            ])->values()->all();

        $expenseBase = DB::table('expenses')->where('branch_id', $branch->id)->where('status', 'posted')
            ->whereBetween('expense_date', [$from, $to]);
        $expenseSummary = (clone $expenseBase)->selectRaw('COUNT(*) AS expenses_count, COALESCE(SUM(amount), 0) AS expenses_total')->first();
        $expenseByCategory = (clone $expenseBase)
            ->join('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->select('expense_categories.name')
            ->selectRaw('COUNT(*) AS expenses_count, COALESCE(SUM(expenses.amount), 0) AS amount')
            ->groupBy('expense_categories.name')->orderByDesc('amount')->get()
            ->map(fn ($row) => ['category' => $row->name, 'count' => (int) $row->expenses_count, 'amount' => (float) $row->amount])->values()->all();

        $purchaseReceiptsTotal = (float) DB::table('goods_receipt_items AS gri')
            ->join('goods_receipts AS gr', 'gr.id', '=', 'gri.goods_receipt_id')
            ->where('gr.branch_id', $branch->id)
            ->whereRaw($this->dateExpression('gr.received_at', $branch).' BETWEEN ? AND ?', [$from, $to])
            ->sum(DB::raw('(gri.received_qty * gri.unit_cost)'));

        $consumption = DB::table('stock_movements AS sm')
            ->join('ingredients AS i', 'i.id', '=', 'sm.ingredient_id')
            ->leftJoin('measurement_units AS mu', 'mu.id', '=', 'i.base_unit_id')
            ->where('sm.branch_id', $branch->id)->where('sm.movement_type', 'consumption')
            ->whereRaw($this->dateExpression('sm.occurred_at', $branch).' BETWEEN ? AND ?', [$from, $to])
            ->select('i.name', 'mu.symbol')
            ->selectRaw('COALESCE(SUM(ABS(sm.quantity_delta)), 0) AS consumed_qty')
            ->groupBy('i.name', 'mu.symbol')->orderByDesc('consumed_qty')->limit(10)->get()
            ->map(fn ($row) => ['ingredient' => $row->name, 'unit' => $row->symbol, 'quantity' => (float) $row->consumed_qty])->values()->all();

        $lowStockBase = DB::table('branch_ingredient_stocks AS bis')
            ->join('ingredients AS i', 'i.id', '=', 'bis.ingredient_id')
            ->leftJoin('measurement_units AS mu', 'mu.id', '=', 'i.base_unit_id')
            ->where('bis.branch_id', $branch->id)->where('i.is_active', true)->whereNull('i.deleted_at')
            ->whereRaw('(bis.on_hand_qty - bis.reserved_qty) <= COALESCE(bis.reorder_level_override, i.reorder_level)');
        $lowStockCount = (clone $lowStockBase)->count();
        $lowStock = (clone $lowStockBase)
            ->select('i.id', 'i.name', 'mu.symbol')
            ->selectRaw('bis.on_hand_qty AS on_hand, bis.reserved_qty AS reserved, (bis.on_hand_qty - bis.reserved_qty) AS available, COALESCE(bis.reorder_level_override, i.reorder_level) AS reorder_level')
            ->orderBy('available')->limit(10)->get()
            ->map(fn ($row) => [
                'ingredient_id' => (int) $row->id,
                'name' => $row->name,
                'unit' => $row->symbol,
                'on_hand' => (float) $row->on_hand,
                'reserved' => (float) $row->reserved,
                'available' => (float) $row->available,
                'reorder_level' => (float) $row->reorder_level,
            ])->values()->all();

        $deliveries = DB::table('deliveries')->where('branch_id', $branch->id)
            ->where(function (QueryBuilder $query) use ($branch, $from, $to): void {
                $query->whereRaw($this->dateExpression('delivered_at', $branch).' BETWEEN ? AND ?', [$from, $to])
                    ->orWhereRaw($this->dateExpression('failed_at', $branch).' BETWEEN ? AND ?', [$from, $to]);
            });
        $deliveryRows = (clone $deliveries)->get(['status', 'picked_up_at', 'delivered_at']);
        $delivered = $deliveryRows->where('status', 'delivered');
        $failed = $deliveryRows->where('status', 'failed');
        $durations = $delivered->filter(fn ($row) => $row->picked_up_at && $row->delivered_at)
            ->map(fn ($row) => CarbonImmutable::parse($row->picked_up_at)->diffInSeconds(CarbonImmutable::parse($row->delivered_at)) / 60);

        $fulfilledCount = (int) ($sales->orders_count ?? 0);
        $netSales = (float) ($sales->net_sales ?? 0);

        return [
            'summary' => [
                'orders_placed' => $totalOrders,
                'fulfilled_orders' => $fulfilledCount,
                'cancelled_orders' => $cancelledOrders,
                'refund_marked_orders_excluded' => $refundMarkedOrders,
                'gross_subtotal' => (float) ($sales->subtotal ?? 0),
                'discounts' => (float) ($sales->discounts ?? 0),
                'taxes' => (float) ($sales->taxes ?? 0),
                'delivery_fees' => (float) ($sales->delivery_fees ?? 0),
                'net_sales' => $netSales,
                'average_order_value' => $fulfilledCount > 0 ? round($netSales / $fulfilledCount, 2) : 0.0,
                'collected_payments' => $collected,
                'unpaid_fulfilled_orders' => (int) ($unpaid->orders_count ?? 0),
                'unpaid_fulfilled_amount' => (float) ($unpaid->amount ?? 0),
                'expenses_count' => (int) ($expenseSummary->expenses_count ?? 0),
                'expenses_total' => (float) ($expenseSummary->expenses_total ?? 0),
                'purchase_receipts_total' => $purchaseReceiptsTotal,
                'low_stock_count' => $lowStockCount,
            ],
            'daily_sales' => $dailySales,
            'top_products' => $topProducts,
            'orders_by_type' => $ordersByType,
            'payment_mix' => $paymentMix,
            'expenses_by_category' => $expenseByCategory,
            'stock_consumption' => $consumption,
            'low_stock' => $lowStock,
            'delivery' => [
                'delivered' => $delivered->count(),
                'failed' => $failed->count(),
                'average_delivery_minutes' => $durations->isNotEmpty() ? round($durations->avg(), 1) : null,
            ],
            'period' => ['from' => $from, 'to' => $to],
            'definitions' => [
                'sales_basis' => 'الطلبات المسلّمة أو المكتملة فقط، مع استبعاد الطلبات الموسومة باسترداد كلي أو جزئي.',
                'collections_basis' => 'المدفوعات ذات الحالة succeeded وتاريخ paid_at داخل الفترة.',
                'profitability' => 'لا يعرض النظام صافي ربح في هذه النسخة؛ تكلفة استهلاك المكونات غير مسجلة حاليًا لكل حركة استهلاك.',
            ],
        ];
    }

    public function exportableOrders(Branch $branch, string $from, string $to): EloquentBuilder
    {
        return $this->fulfilledOrders($branch, $from, $to)
            ->whereNotIn('payment_status', self::REFUND_MARKED_STATUSES)
            ->orderBy('placed_at')->orderBy('id');
    }

    private function ordersInPeriod(Branch $branch, string $from, string $to): EloquentBuilder
    {
        return Order::query()->where('branch_id', $branch->id)
            ->whereRaw($this->dateExpression('placed_at', $branch).' BETWEEN ? AND ?', [$from, $to]);
    }

    private function fulfilledOrders(Branch $branch, string $from, string $to): EloquentBuilder
    {
        return $this->ordersInPeriod($branch, $from, $to)->whereIn('order_status', self::FULFILLED_STATUSES);
    }

    private function dateExpression(string $column, Branch $branch): string
    {
        // Column names are internal constants; timezone is sanitized before interpolation.
        $timezone = (string) ($branch->restaurant?->timezone ?: config('app.timezone', 'UTC'));
        $timezone = preg_replace('/[^A-Za-z0-9_+\/-]/', '', $timezone) ?: 'UTC';

        return match (DB::connection()->getDriverName()) {
            'pgsql' => "DATE({$column} AT TIME ZONE '{$timezone}')",
            'mysql' => "DATE(CONVERT_TZ({$column}, '+00:00', '{$timezone}'))",
            default => "DATE({$column})",
        };
    }

    private function paymentMethodLabel(string $method): string
    {
        return match ($method) {
            'cash' => 'نقدي',
            'vodafone_cash' => 'فودافون كاش',
            default => $method,
        };
    }

    private function orderTypeLabel(string $type): string
    {
        return match ($type) {
            'delivery' => 'توصيل',
            'pickup' => 'استلام',
            'dine_in' => 'داخل المطعم',
            default => $type,
        };
    }
}
