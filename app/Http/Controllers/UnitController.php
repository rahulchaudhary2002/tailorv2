<?php

namespace App\Http\Controllers;

use App\Http\Requests\Unit\StoreRequest;
use App\Http\Requests\Unit\UpdateRequest;
use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    /**
     * Display a listing of units.
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $unitsQuery = Unit::query();

        if ($q !== '') {
            $unitsQuery->where(function ($query) use ($q): void {
                $query->where('name', 'like', '%' . $q . '%')
                    ->orWhere('symbol', 'like', '%' . $q . '%');
            });
        }

        $reporting = [
            'total' => (clone $unitsQuery)->count(),
            'added_this_week' => (clone $unitsQuery)->where('created_at', '>=', now()->startOfWeek())->count(),
            'added_this_month' => (clone $unitsQuery)->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            'added_last_30_days' => (clone $unitsQuery)->where('created_at', '>=', now()->subDays(30))->count(),
        ];

        $units = $unitsQuery
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('modules.unit.index', compact('units', 'reporting'));
    }

    /**
     * Show the form for creating a new unit.
     */
    public function create()
    {
        return view('modules.unit.create');
    }

    /**
     * Store a newly created unit in storage.
     */
    public function store(StoreRequest $request)
    {
        Unit::create($request->validated());

        return redirect()
            ->route('unit.index')
            ->with('success', 'Unit created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Unit $unit)
    {
        return redirect()->route('unit.edit', $unit);
    }

    /**
     * Show the form for editing the specified unit.
     */
    public function edit(Unit $unit)
    {
        return view('modules.unit.edit', compact('unit'));
    }

    /**
     * Update the specified unit in storage.
     */
    public function update(UpdateRequest $request, Unit $unit)
    {
        $unit->update($request->validated());

        return redirect()
            ->route('unit.index')
            ->with('success', 'Unit updated successfully.');
    }

    /**
     * Remove the specified unit from storage.
     */
    public function destroy(Unit $unit)
    {
        $unit->delete();

        return redirect()
            ->route('unit.index')
            ->with('success', 'Unit deleted successfully.');
    }
}
