<?php

namespace App\Modules\Reports\Http\Controllers;

use App\Modules\Branches\Models\Branch;
use App\Modules\Orders\Models\Order;
use App\Modules\Reports\Http\Requests\ReportFiltersRequest;
use App\Modules\Reports\Services\ReportAnalyticsService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController
{
    public function index(ReportFiltersRequest $request, Branch $branch, ReportAnalyticsService $reports)
    {
        [$from, $to] = $this->period($request, $branch);

        return Inertia::render('Reports/Index', [
            'branch' => $branch->only(['id', 'code', 'name']),
            'filters' => ['from' => $from, 'to' => $to],
            'report' => $reports->build($branch, $from, $to),
        ]);
    }

    public function export(ReportFiltersRequest $request, Branch $branch, ReportAnalyticsService $reports): StreamedResponse
    {
        [$from, $to] = $this->period($request, $branch);
        $query = $reports->exportableOrders($branch, $from, $to);
        $filename = 'branch-'.$branch->code.'-orders-'.$from.'-'.$to.'.csv';

        return response()->streamDownload(function () use ($query): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['رقم الطلب', 'تاريخ الطلب', 'نوع الطلب', 'الحالة', 'طريقة الدفع', 'حالة الدفع', 'المجموع الفرعي', 'الخصم', 'الضريبة', 'رسوم التوصيل', 'الإجمالي', 'العملة'], ',', chr(34), '');

            foreach ($query->cursor() as $order) {
                fputcsv($output, [
                    $order->order_number,
                    $order->placed_at?->toIso8601String(),
                    $order->order_type,
                    $order->order_status,
                    $order->payment_method,
                    $order->payment_status,
                    $order->subtotal,
                    $order->discount_total,
                    $order->tax_total,
                    $order->delivery_fee,
                    $order->total,
                    $order->currency,
                ], ',', chr(34), '');
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function period(ReportFiltersRequest $request, Branch $branch): array
    {
        $timezone = $branch->restaurant?->timezone ?: config('app.timezone', 'UTC');
        $today = CarbonImmutable::now($timezone)->toDateString();
        $from = $request->validated('from') ?: CarbonImmutable::parse($today, $timezone)->subDays(29)->toDateString();
        $to = $request->validated('to') ?: $today;

        return [$from, $to];
    }
}
