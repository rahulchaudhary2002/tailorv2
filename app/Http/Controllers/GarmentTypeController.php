<?php

namespace App\Http\Controllers;

use App\Http\Requests\GarmentType\StoreRequest;
use App\Http\Requests\GarmentType\UpdateRequest;
use App\Http\Requests\GarmentTypeMeasurement\StoreRequest as MeasurementStoreRequest;
use App\Http\Requests\GarmentTypeMeasurement\UpdateRequest as MeasurementUpdateRequest;
use App\Models\GarmentType;
use App\Models\GarmentTypeMeasurement;
use App\Models\GarmentTypeTailoringPackage;
use App\Models\Unit;
use Illuminate\Http\Request;

class GarmentTypeController extends Controller
{
    /**
     * Display a listing of garment types.
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $garmentTypesQuery = GarmentType::query()
            ->withCount('measurements')
            ->withCount('tailoringPackages')
            ->latest();

        if ($q !== '') {
            $garmentTypesQuery->where(function ($query) use ($q): void {
                $query->where('title', 'like', '%' . $q . '%');
            });
        }

        $reporting = [
            'total' => (clone $garmentTypesQuery)->count(),
            'added_this_week' => (clone $garmentTypesQuery)->where('created_at', '>=', now()->startOfWeek())->count(),
            'added_this_month' => (clone $garmentTypesQuery)->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            'added_last_30_days' => (clone $garmentTypesQuery)->where('created_at', '>=', now()->subDays(30))->count(),
        ];

        $garmentTypes = $garmentTypesQuery
            ->paginate(10)
            ->withQueryString();

        return view('modules.garment_type.index', compact('garmentTypes', 'reporting'));
    }

    /**
     * Show the form for creating a new garment type.
     */
    public function create()
    {
        return view('modules.garment_type.create');
    }

    /**
     * Store a newly created garment type in storage.
     */
    public function store(StoreRequest $request)
    {
        $garmentType = GarmentType::create($request->validated());

        return redirect()
            ->route('garmentType.edit', ['garmentType' => $garmentType, 'tab' => 'measurements'])
            ->with('success', 'Garment type created successfully. You can now add measurements.');
    }

    /**
     * Show the form for editing the specified garment type.
     */
    public function edit(GarmentType $garmentType)
    {
        $garmentType->load([
            'measurements.unit:id,name,symbol',
            'tailoringPackages',
        ]);
        $units = Unit::query()->orderBy('name')->get(['id', 'name', 'symbol']);

        return view('modules.garment_type.edit', compact('garmentType', 'units'));
    }

    /**
     * Update the specified garment type in storage.
     */
    public function update(UpdateRequest $request, GarmentType $garmentType)
    {
        $garmentType->update($request->validated());

        return redirect()
            ->route('garmentType.edit', ['garmentType' => $garmentType, 'tab' => 'details'])
            ->with('success', 'Garment type updated successfully.');
    }

    /**
     * Remove the specified garment type from storage.
     */
    public function destroy(GarmentType $garmentType)
    {
        $garmentType->delete();

        return redirect()
            ->route('garmentType.index')
            ->with('success', 'Garment type deleted successfully.');
    }

    /**
     * Store a measurement for the garment type.
     */
    public function storeMeasurement(MeasurementStoreRequest $request, GarmentType $garmentType)
    {
        $garmentType->measurements()->create($request->validated());

        return redirect()
            ->route('garmentType.edit', ['garmentType' => $garmentType, 'tab' => 'measurements'])
            ->with('success', 'Measurement added successfully.');
    }

    /**
     * Update a measurement of the garment type.
     */
    public function updateMeasurement(
        MeasurementUpdateRequest $request,
        GarmentType $garmentType,
        GarmentTypeMeasurement $measurement
    ) {
        if ((int) $measurement->garment_type_id !== (int) $garmentType->id) {
            abort(404);
        }

        $measurement->update($request->validated());

        return redirect()
            ->route('garmentType.edit', ['garmentType' => $garmentType, 'tab' => 'measurements'])
            ->with('success', 'Measurement updated successfully.');
    }

    /**
     * Remove a measurement of the garment type.
     */
    public function destroyMeasurement(GarmentType $garmentType, GarmentTypeMeasurement $measurement)
    {
        if ((int) $measurement->garment_type_id !== (int) $garmentType->id) {
            abort(404);
        }

        $measurement->delete();

        return redirect()
            ->route('garmentType.edit', ['garmentType' => $garmentType, 'tab' => 'measurements'])
            ->with('success', 'Measurement deleted successfully.');
    }

    /**
     * Store a tailoring package for garment type.
     */
    public function storeTailoringPackage(Request $request, GarmentType $garmentType)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:255'],
            'order' => ['required', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $garmentType->tailoringPackages()->create([
            'name' => trim((string) $validated['name']),
            'amount' => (float) $validated['amount'],
            'description' => isset($validated['description']) ? trim((string) $validated['description']) : null,
            'order' => (int) $validated['order'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return redirect()
            ->route('garmentType.edit', ['garmentType' => $garmentType, 'tab' => 'tailoring'])
            ->with('success', 'Tailoring package added successfully.');
    }

    /**
     * Update a tailoring package of garment type.
     */
    public function updateTailoringPackage(Request $request, GarmentType $garmentType, GarmentTypeTailoringPackage $package)
    {
        if ((int) $package->garment_type_id !== (int) $garmentType->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:255'],
            'order' => ['required', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $package->update([
            'name' => trim((string) $validated['name']),
            'amount' => (float) $validated['amount'],
            'description' => isset($validated['description']) ? trim((string) $validated['description']) : null,
            'order' => (int) $validated['order'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return redirect()
            ->route('garmentType.edit', ['garmentType' => $garmentType, 'tab' => 'tailoring'])
            ->with('success', 'Tailoring package updated successfully.');
    }

    /**
     * Remove a tailoring package of garment type.
     */
    public function destroyTailoringPackage(GarmentType $garmentType, GarmentTypeTailoringPackage $package)
    {
        if ((int) $package->garment_type_id !== (int) $garmentType->id) {
            abort(404);
        }

        $package->delete();

        return redirect()
            ->route('garmentType.edit', ['garmentType' => $garmentType, 'tab' => 'tailoring'])
            ->with('success', 'Tailoring package deleted successfully.');
    }
}
