<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderTask;
use App\Models\Outlet;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request): \Illuminate\View\View
    {
        $user = auth()->user();
        abort_unless($user, 403);

        $outletId = (int) ($user->current_outlet_id ?? 0);
        $outlets = Outlet::query()->orderBy('name')->get();

        $fromDate = trim((string) $request->query('from_date', now()->toDateString()));
        $toDate = trim((string) $request->query('to_date', now()->toDateString()));

        try {
            $start = Carbon::parse($fromDate)->startOfDay();
            $end = Carbon::parse($toDate)->endOfDay();
        } catch (\Exception) {
            $start = now()->startOfDay();
            $end = now()->endOfDay();
            $fromDate = now()->toDateString();
            $toDate = now()->toDateString();
        }

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
            [$fromDate, $toDate] = [$start->toDateString(), $end->toDateString()];
        }

        $ordersQuery = Order::query()
            ->with(['customer:id,name,phone', 'items'])
            ->when($outletId > 0, fn ($q) => $q->where('outlet_id', $outletId))
            ->whereBetween('ordered_at', [$start, $end])
            ->where('status', '!=', Order::STATUS_CANCELLED)
            ->orderByRaw("CASE payment_status WHEN 'unpaid' THEN 1 WHEN 'partial' THEN 2 WHEN 'paid' THEN 3 ELSE 4 END")
            ->orderBy('ordered_at', 'desc');

        $orders = $ordersQuery->get();

        $totalOrders = $orders->count();
        $totalRevenue = $orders->sum(fn ($o) => $o->payableAmount());
        $totalAdvance = $orders->sum(fn ($o) => (float) $o->advance_payment_amount);
        $totalDue = $orders->sum(fn ($o) => $o->dueAmount());
        $totalTailoring = $orders->sum(fn ($o) => (float) $o->tailoring_amount);
        $totalDiscount = $orders->sum(fn ($o) => (float) $o->discount_amount);

        $paidOrders = $orders->where('payment_status', Order::PAYMENT_STATUS_PAID)->count();
        $partialOrders = $orders->where('payment_status', Order::PAYMENT_STATUS_PARTIAL)->count();
        $unpaidOrders = $orders->where('payment_status', Order::PAYMENT_STATUS_UNPAID)->count();

        $deliveredOrders = $orders->where('status', Order::STATUS_DELIVERED)->count();
        $completedOrders = $orders->where('status', Order::STATUS_COMPLETED)->count();
        $cancelledCount = Order::query()
            ->when($outletId > 0, fn ($q) => $q->where('outlet_id', $outletId))
            ->whereBetween('ordered_at', [$start, $end])
            ->where('status', Order::STATUS_CANCELLED)
            ->count();

        $itemCategoryBreakdown = $orders->flatMap(fn ($o) => $o->items)
            ->groupBy('item_category')
            ->map(fn ($items) => [
                'count' => $items->count(),
                'total' => $items->sum(fn ($i) => (float) $i->line_total),
            ]);

        $tasksQuery = OrderTask::query()
            ->whereHas('order', function ($q) use ($outletId, $start, $end): void {
                $q->when($outletId > 0, fn ($inner) => $inner->where('outlet_id', $outletId))
                    ->whereBetween('ordered_at', [$start, $end]);
            });

        $totalWorkerPayable = (float) (clone $tasksQuery)->sum('payable_amount');
        $totalWorkerPaid = (float) (clone $tasksQuery)->whereNotNull('paid_at')->sum('payable_amount');

        $dailyBreakdown = Order::query()
            ->select(
                DB::raw('DATE(ordered_at) as day'),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('COALESCE(SUM(GREATEST(0, COALESCE(subtotal_amount, 0) - COALESCE(discount_amount, 0) + COALESCE(tailoring_amount, 0) + CASE WHEN vat_enabled IS TRUE THEN COALESCE(vat_amount, 0) ELSE 0 END)), 0) as revenue')
            )
            ->when($outletId > 0, fn ($q) => $q->where('outlet_id', $outletId))
            ->whereBetween('ordered_at', [$start, $end])
            ->where('status', '!=', Order::STATUS_CANCELLED)
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        return view('modules.report.index', compact(
            'outlets', 'outletId', 'orders',
            'fromDate', 'toDate', 'start', 'end',
            'totalOrders', 'totalRevenue', 'totalAdvance', 'totalDue',
            'totalTailoring', 'totalDiscount',
            'paidOrders', 'partialOrders', 'unpaidOrders',
            'deliveredOrders', 'completedOrders', 'cancelledCount',
            'itemCategoryBreakdown',
            'totalWorkerPayable', 'totalWorkerPaid',
            'dailyBreakdown',
        ));
    }
}
