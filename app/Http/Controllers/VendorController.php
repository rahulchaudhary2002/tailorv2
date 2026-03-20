<?php

namespace App\Http\Controllers;

use App\Http\Requests\Vendor\StoreRequest;
use App\Http\Requests\Vendor\UpdateRequest;
use App\Models\Vendor;
use App\Models\VendorType;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    /**
     * Display a listing of vendors.
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $qLower = mb_strtolower($q);
        $vendorTypeId = (int) $request->query('vendor_type_id', 0);

        $vendorsQuery = Vendor::query()
            ->with('vendorType:id,name')
            ->latest();

        if ($q !== '') {
            $vendorsQuery->where(function ($query) use ($qLower): void {
                $query->whereRaw('LOWER(name) LIKE ?', ['%' . $qLower . '%'])
                    ->orWhereRaw('LOWER(email) LIKE ?', ['%' . $qLower . '%'])
                    ->orWhereRaw('LOWER(phone) LIKE ?', ['%' . $qLower . '%']);
            });
        }

        if ($vendorTypeId > 0) {
            $vendorsQuery->where('vendor_type_id', $vendorTypeId);
        }

        $vendors = $vendorsQuery
            ->paginate(10)
            ->withQueryString();

        $vendorTypes = VendorType::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('modules.vendor.index', compact('vendors', 'vendorTypes'));
    }

    /**
     * Show the form for creating a new vendor.
     */
    public function create()
    {
        $vendorTypes = VendorType::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('modules.vendor.create', compact('vendorTypes'));
    }

    /**
     * Store a newly created vendor in storage.
     */
    public function store(StoreRequest $request)
    {
        $data = $this->preparePayload($request->validated());

        Vendor::create($data);

        return redirect()
            ->route('vendor.index')
            ->with('success', 'Vendor created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Vendor $vendor)
    {
        return redirect()->route('vendor.edit', $vendor);
    }

    /**
     * Show the form for editing the specified vendor.
     */
    public function edit(Vendor $vendor)
    {
        $vendorTypes = VendorType::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('modules.vendor.edit', compact('vendor', 'vendorTypes'));
    }

    /**
     * Update the specified vendor in storage.
     */
    public function update(UpdateRequest $request, Vendor $vendor)
    {
        $data = $this->preparePayload($request->validated());

        $vendor->update($data);

        return redirect()
            ->route('vendor.index')
            ->with('success', 'Vendor updated successfully.');
    }

    /**
     * Remove the specified vendor from storage.
     */
    public function destroy(Vendor $vendor)
    {
        $vendor->delete();

        return redirect()
            ->route('vendor.index')
            ->with('success', 'Vendor deleted successfully.');
    }

    /**
     * Normalize request payload and resolve dynamic vendor type.
     */
    private function preparePayload(array $validated): array
    {
        $vendorTypeName = trim((string) ($validated['vendor_type'] ?? ''));
        $vendorType = VendorType::query()->firstOrCreate([
            'name' => $vendorTypeName,
        ]);

        unset($validated['vendor_type']);

        $validated['vendor_type_id'] = $vendorType->id;

        return $validated;
    }
}
