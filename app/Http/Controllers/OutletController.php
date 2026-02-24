<?php

namespace App\Http\Controllers;

use App\Http\Requests\Outlet\StoreRequest;
use App\Http\Requests\Outlet\SwitchRequest;
use App\Http\Requests\Outlet\UpdateRequest;
use App\Models\InventoryLocation;
use App\Models\Outlet;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OutletController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $outletsQuery = Outlet::query()
            ->withCount([
                'users as users_count' => function ($query) {
                    $query->where('users.is_super_admin', false);
                },
            ]);

        if ($q !== '') {
            $outletsQuery->where(function ($query) use ($q): void {
                $query->where('name', 'like', '%' . $q . '%')
                    ->orWhere('code', 'like', '%' . $q . '%')
                    ->orWhere('address', 'like', '%' . $q . '%');
            });
        }

        $reporting = [
            'total' => (clone $outletsQuery)->count(),
            'added_this_week' => (clone $outletsQuery)->where('created_at', '>=', now()->startOfWeek())->count(),
            'added_this_month' => (clone $outletsQuery)->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            'added_last_30_days' => (clone $outletsQuery)->where('created_at', '>=', now()->subDays(30))->count(),
        ];

        $outlets = $outletsQuery
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('modules.outlet.index', compact('outlets', 'reporting'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('modules.outlet.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        $outlet = Outlet::create($request->validated());

        InventoryLocation::query()->updateOrCreate(
            [
                'outlet_id' => $outlet->id,
                'type' => InventoryLocation::TYPE_OUTLET,
            ],
            [
                'name' => $outlet->name . ' Inventory',
                'type' => InventoryLocation::TYPE_OUTLET,
                'outlet_id' => $outlet->id,
                'code' => 'OUT-' . Str::upper($outlet->code),
                'is_active' => true,
            ]
        );

        $user = $request->user();
        $outlet->users()->syncWithoutDetaching([$user->id]);

        if (!$user->current_outlet_id) {
            $user->current_outlet_id = $outlet->id;
            $user->save();
        }

        return redirect()
            ->route('outlet.index')
            ->with('success', 'Outlet created successfully.');
    }

    /**
     * Switch the current outlet for the authenticated user.
     */
    public function switch(SwitchRequest $request)
    {
        $user = $request->user();
        $user->current_outlet_id = $request->input('outlet_id');
        $user->save();

        return redirect()->back();
    }

    /**
     * Display the specified resource.
     */
    public function show(Outlet $outlet)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Outlet $outlet)
    {
        return view('modules.outlet.edit', compact('outlet'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, Outlet $outlet)
    {
        $outlet->update($request->validated());

        InventoryLocation::query()->updateOrCreate(
            [
                'outlet_id' => $outlet->id,
                'type' => InventoryLocation::TYPE_OUTLET,
            ],
            [
                'name' => $outlet->name . ' Inventory',
                'type' => InventoryLocation::TYPE_OUTLET,
                'outlet_id' => $outlet->id,
                'code' => 'OUT-' . Str::upper($outlet->code),
                'is_active' => true,
            ]
        );

        return redirect()
            ->route('outlet.index')
            ->with('success', 'Outlet updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Outlet $outlet)
    {
        $outlet->delete();

        return redirect()
            ->route('outlet.index')
            ->with('success', 'Outlet deleted successfully.');
    }
}
