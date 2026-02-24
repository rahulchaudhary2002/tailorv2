<?php

namespace App\Http\Controllers;

use App\Http\Requests\Customer\StoreRequest;
use App\Http\Requests\Customer\UpdateMeasurementsRequest;
use App\Http\Requests\Customer\UpdateRequest;
use App\Models\Customer;
use App\Models\GarmentType;
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

        $customersQuery = Customer::query();

        if ($q !== '') {
            $customersQuery->where(function ($query) use ($q): void {
                $query->where('name', 'like', '%' . $q . '%')
                    ->orWhere('email', 'like', '%' . $q . '%')
                    ->orWhere('phone', 'like', '%' . $q . '%')
                    ->orWhere('address', 'like', '%' . $q . '%');
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

        return redirect()
            ->route('customer.edit', ['customer' => $customer, 'tab' => 'measurements'])
            ->with('success', 'Customer created successfully. You can now add measurements.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        $customer->load([
            'customerGarmentTypes.garmentType:id,title',
            'customerGarmentTypes.measurements',
        ]);

        return view('modules.customer.show', compact('customer'));
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
            ->orderBy('title')
            ->get(['id', 'title']);

        return view('modules.customer.edit', compact('customer', 'garmentTypes'));
    }

    /**
     * Update customer details.
     */
    public function update(UpdateRequest $request, Customer $customer)
    {
        $validated = $request->validated();

        $customer->update($this->extractCustomerData($validated));

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

        return redirect()
            ->route('customer.edit', ['customer' => $customer, 'tab' => 'measurements'])
            ->with('success', 'Customer measurements updated successfully.');
    }

    /**
     * Remove the specified customer from storage.
     */
    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()
            ->route('customer.index')
            ->with('success', 'Customer deleted successfully.');
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
