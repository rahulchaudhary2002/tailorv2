<?php

namespace App\Http\Controllers;

use App\Http\Requests\Customer\StoreRequest;
use App\Http\Requests\Customer\UpdateMeasurementsRequest;
use App\Http\Requests\Customer\UpdateRequest;
use App\Models\Customer;
use App\Models\GarmentType;
use App\Models\Order;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers.
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $qLower = mb_strtolower($q);

        $customersQuery = Customer::query();

        if ($q !== '') {
            $customersQuery->where(function ($query) use ($qLower): void {
                $query->whereRaw('LOWER(name) LIKE ?', ['%' . $qLower . '%'])
                    ->orWhereRaw('LOWER(email) LIKE ?', ['%' . $qLower . '%'])
                    ->orWhereRaw('LOWER(phone) LIKE ?', ['%' . $qLower . '%'])
                    ->orWhereRaw('LOWER(address) LIKE ?', ['%' . $qLower . '%']);
            });
        }

        $customers = $customersQuery
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total' => Customer::query()->count(),
            'added_this_week' => Customer::query()
                ->where('created_at', '>=', now()->startOfWeek())
                ->count(),
            'added_this_month' => Customer::query()
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count(),
            'added_last_30_days' => Customer::query()
                ->where('created_at', '>=', now()->subDays(30))
                ->count(),
        ];

        return view('modules.customer.index', compact('customers', 'stats'));
    }

    /**
     * Show the form for creating a new customer.
     */
    public function create()
    {
        return view('modules.customer.create');
    }

    /**
     * Store a newly created customer in storage.
     */
    public function store(StoreRequest $request)
    {
        $customer = Customer::create($request->validated());

        $this->notifyCustomerRecipients(
            'Customer created',
            'Customer ' . $customer->name . ' was created.',
            route('customer.show', $customer)
        );

        return redirect()
            ->route('customer.edit', ['customer' => $customer, 'tab' => 'measurements'])
            ->with('success', 'Customer created successfully. You can now add measurements.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        $currentOutletId = (int) (auth()->user()?->current_outlet_id ?? 0);

        $customer->load([
            'customerGarmentTypes.garmentType:id,title',
            'customerGarmentTypes.measurements',
        ]);

        $ordersQuery = $customer->orders()
            ->with(['outlet:id,name'])
            ->when($currentOutletId > 0, function ($query) use ($currentOutletId): void {
                $query->where('outlet_id', $currentOutletId);
            });

        $recentOrders = (clone $ordersQuery)
            ->latest('ordered_at')
            ->limit(10)
            ->get([
                'id',
                'order_number',
                'outlet_id',
                'ordered_at',
                'delivery_due_at',
                'status',
                'payment_status',
                'advance_payment_amount',
                'payment_method',
                'subtotal_amount',
                'discount_amount',
                'tailoring_amount',
                'vat_enabled',
                'vat_amount',
            ]);

        $paymentHistory = (clone $ordersQuery)
            ->where(function ($query): void {
                $query->where('advance_payment_amount', '>', 0)
                    ->orWhere('payment_status', '!=', Order::PAYMENT_STATUS_UNPAID);
            })
            ->latest('updated_at')
            ->latest('ordered_at')
            ->get([
                'id',
                'order_number',
                'outlet_id',
                'ordered_at',
                'delivery_due_at',
                'updated_at',
                'payment_status',
                'payment_method',
                'advance_payment_amount',
                'subtotal_amount',
                'discount_amount',
                'tailoring_amount',
                'vat_enabled',
                'vat_amount',
            ]);

        $orderCount = (clone $ordersQuery)->count();
        $allOrders = (clone $ordersQuery)->get([
            'id',
            'ordered_at',
            'payment_status',
            'advance_payment_amount',
            'payment_method',
            'subtotal_amount',
            'discount_amount',
            'tailoring_amount',
            'vat_enabled',
            'vat_amount',
        ]);
        $totalSpent = (float) $allOrders->sum(fn (Order $order) => $order->payableAmount());
        $totalPaid = (float) $allOrders->sum(fn (Order $order) => $order->paidAmount());
        $totalAdvancePaid = (float) $allOrders->sum(fn (Order $order) => (float) ($order->advance_payment_amount ?? 0));
        $totalDue = (float) $allOrders->sum(fn (Order $order) => $order->dueAmount());
        $paidOrderCount = (int) $allOrders->filter(fn (Order $order) => (string) $order->payment_status === Order::PAYMENT_STATUS_PAID)->count();
        $dueOrderCount = (int) $allOrders->filter(fn (Order $order) => $order->dueAmount() > 0.0001)->count();
        $lastOrderDate = (clone $ordersQuery)->latest('ordered_at')->value('ordered_at');

        return view('modules.customer.show', compact(
            'customer',
            'recentOrders',
            'paymentHistory',
            'orderCount',
            'totalSpent',
            'totalPaid',
            'totalAdvancePaid',
            'totalDue',
            'paidOrderCount',
            'dueOrderCount',
            'lastOrderDate'
        ));
    }

    /**
     * Show the form for editing the specified customer.
     */
    public function edit(Customer $customer)
    {
        $customer->load([
            'customerGarmentTypes.measurements',
            'customerGarmentTypes.garmentType:id,title',
        ]);

        $garmentTypes = GarmentType::query()
            ->with(['measurements.unit:id,name,symbol'])
            ->ordered()
            ->get(['id', 'title', 'sort_order']);

        return view('modules.customer.edit', compact('customer', 'garmentTypes'));
    }

    /**
     * Update customer details.
     */
    public function update(UpdateRequest $request, Customer $customer)
    {
        $validated = $request->validated();

        $customer->update($this->extractCustomerData($validated));

        $this->notifyCustomerRecipients(
            'Customer updated',
            'Customer ' . $customer->name . ' details were updated.',
            route('customer.show', $customer)
        );

        return redirect()
            ->route('customer.edit', ['customer' => $customer, 'tab' => 'details'])
            ->with('success', 'Customer details updated successfully.');
    }

    /**
     * Update customer measurements.
     */
    public function updateMeasurements(UpdateMeasurementsRequest $request, Customer $customer)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($customer, $validated) {
            $this->syncCustomerMeasurements($customer, $validated);
        });

        $this->notifyCustomerRecipients(
            'Customer measurements updated',
            'Measurements for customer ' . $customer->name . ' were updated.',
            route('customer.show', $customer)
        );

        return redirect()
            ->route('customer.edit', ['customer' => $customer, 'tab' => 'measurements'])
            ->with('success', 'Customer measurements updated successfully.');
    }

    /**
     * Remove the specified customer from storage.
     */
    public function destroy(Customer $customer)
    {
        $customerName = $customer->name;
        $customer->delete();

        $this->notifyCustomerRecipients(
            'Customer deleted',
            'Customer ' . $customerName . ' was deleted.',
            route('customer.index')
        );

        return redirect()
            ->route('customer.index')
            ->with('success', 'Customer deleted successfully.');
    }

    private function notifyCustomerRecipients(string $title, string $message, string $url): void
    {
        $actorName = (string) (auth()->user()?->name ?: 'System');

        app(NotificationService::class)->notifyPermission(
            'receive-customer-notifications',
            (int) (auth()->user()?->current_outlet_id ?? 0),
            [
                'title' => $title,
                'message' => $actorName . ': ' . $message,
                'url' => $url,
                'module' => 'Customer',
            ],
            array_filter([(int) auth()->id()])
        );
    }

    /**
     * Keep only core customer fields.
     */
    private function extractCustomerData(array $validated): array
    {
        return collect($validated)->only([
            'name',
            'email',
            'phone',
            'customer_type',
            'address',
        ])->all();
    }

    /**
     * Sync selected garment types and their measurement rows.
     */
    private function syncCustomerMeasurements(Customer $customer, array $validated): void
    {
        $garmentTypeIds = collect($validated['garment_type_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $customer->customerGarmentTypes()->delete();

        if ($garmentTypeIds->isEmpty()) {
            return;
        }

        $pivotByGarmentTypeId = [];

        foreach ($garmentTypeIds as $garmentTypeId) {
            $customerGarmentType = $customer->customerGarmentTypes()->create([
                'garment_type_id' => $garmentTypeId,
            ]);

            $pivotByGarmentTypeId[$garmentTypeId] = $customerGarmentType;
        }

        $rows = collect($validated['measurements'] ?? [])->values();

        foreach ($rows as $index => $row) {
            $garmentTypeId = (int) ($row['garment_type_id'] ?? 0);
            $customerGarmentType = $pivotByGarmentTypeId[$garmentTypeId] ?? null;

            if ($customerGarmentType === null) {
                continue;
            }

            $type = trim((string) ($row['type'] ?? ''));
            $measurement = trim((string) ($row['measurement'] ?? ''));
            $unit = trim((string) ($row['unit'] ?? ''));

            if ($type === '' || $measurement === '' || $unit === '') {
                continue;
            }

            $customerGarmentType->measurements()->create([
                'type' => $type,
                'measurement' => $measurement,
                'unit' => $unit,
                'order' => $index + 1,
            ]);
        }
    }
}
