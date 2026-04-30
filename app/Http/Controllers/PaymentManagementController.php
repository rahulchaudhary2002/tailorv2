<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderTask;
use App\Services\OrderTaskService;
use Illuminate\Http\Request;

class PaymentManagementController extends Controller
{
    public function __construct(private readonly OrderTaskService $taskService)
    {
    }

    public function index()
    {
        $outletId = (int) (auth()->user()?->current_outlet_id ?? 0);
        $this->taskService->syncForOutlet($outletId);

        $orders = Order::query()
            ->with(['customer:id,name,phone'])
            ->where('outlet_id', $outletId)
            ->latest('ordered_at')
            ->get();

        $tasks = OrderTask::query()
            ->with(['worker:id,name', 'order:id,order_number,customer_id,outlet_id', 'order.customer:id,name'])
            ->whereHas('order', function ($query) use ($outletId): void {
                $query->where('outlet_id', $outletId);
            })
            ->latest('id')
            ->get();

        $workerTasks = $tasks
            ->filter(fn ($task) => (int) ($task->worker_id ?? 0) > 0 && $task->status !== OrderTask::STATUS_CANCELLED)
            ->values();

        $customerPayments = $orders
            ->groupBy(fn ($order) => (string) ($order->customer?->id ?? 0))
            ->map(function ($group) {
                $customer = $group->first()?->customer;
                $payable = $group->sum(fn ($order) => (float) $order->payableAmount());
                $received = $group->sum(fn ($order) => (float) $order->paidAmount());

                return [
                    'customer' => $customer,
                    'orders' => $group->count(),
                    'payable' => $payable,
                    'received' => $received,
                    'due' => max(0, $payable - $received),
                ];
            })
            ->values();

        $workerPayments = $tasks
            ->filter(fn ($task) => (int) ($task->worker_id ?? 0) > 0 && $task->status !== OrderTask::STATUS_CANCELLED)
            ->groupBy(fn ($task) => (string) $task->worker_id)
            ->map(function ($group) {
                $worker = $group->first()?->worker;
                $payable = $group->sum(fn ($task) => (float) ($task->payable_amount ?? 0));
                $paid = $group->filter(fn ($task) => $task->paid_at !== null)
                    ->sum(fn ($task) => (float) ($task->payable_amount ?? 0));

                return [
                    'worker' => $worker,
                    'tasks' => $group->count(),
                    'payable' => $payable,
                    'paid' => $paid,
                    'due' => max(0, $payable - $paid),
                ];
            })
            ->values();

        $payableTasks = $workerTasks
            ->filter(fn ($task) => $task->paid_at === null)
            ->values();

        $reporting = [
            'customer_received' => $orders->sum(fn ($order) => (float) $order->paidAmount()),
            'worker_payable' => $workerTasks->sum(fn ($task) => (float) ($task->payable_amount ?? 0)),
            'worker_paid' => $workerTasks->filter(fn ($task) => $task->paid_at !== null)->sum(fn ($task) => (float) ($task->payable_amount ?? 0)),
            'pending_worker_due' => $workerTasks->filter(fn ($task) => $task->paid_at === null)->sum(fn ($task) => (float) ($task->payable_amount ?? 0)),
        ];

        return view('modules.payment_management.index', compact(
            'customerPayments',
            'workerPayments',
            'payableTasks',
            'reporting'
        ));
    }

    public function payWorkerTask(OrderTask $task)
    {
        $outletId = (int) (auth()->user()?->current_outlet_id ?? 0);
        $belongs = $task->order()
            ->where('outlet_id', $outletId)
            ->exists();

        if (!$belongs) {
            abort(404);
        }

        if ((int) ($task->worker_id ?? 0) < 1) {
            return back()->with('error', 'Assign a worker before marking task as paid.');
        }

        $task->paid_at = $task->paid_at ?? now();
        $task->slip_received_at = $task->slip_received_at ?? now();
        $task->save();

        return back()->with('success', 'Worker payment marked as paid.');
    }
}
