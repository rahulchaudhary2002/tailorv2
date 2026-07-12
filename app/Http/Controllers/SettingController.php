<?php

namespace App\Http\Controllers;

use App\Http\Requests\Setting\UpdateRequest;
use App\Models\Setting;

class SettingController extends Controller
{
    /**
     * Display application settings.
     */
    public function index()
    {
        $settings = [
            'printer_phone_number' => Setting::valueFor('printer_phone_number', ''),
            'amount_decimals_enabled' => Setting::valueFor('amount_decimals_enabled', '0') === '1',
            'amount_round_up' => Setting::valueFor('amount_round_up', '0') === '1',
        ];

        return view('modules.setting.index', compact('settings'));
    }

    /**
     * Update configurable application settings.
     */
    public function update(UpdateRequest $request)
    {
        $validated = $request->validated();

        Setting::query()->updateOrCreate(
            ['key' => 'printer_phone_number'],
            ['value' => $validated['printer_phone_number'] ?? '']
        );

        Setting::query()->updateOrCreate(
            ['key' => 'amount_decimals_enabled'],
            ['value' => $request->boolean('amount_decimals_enabled') ? '1' : '0']
        );

        Setting::query()->updateOrCreate(
            ['key' => 'amount_round_up'],
            ['value' => $request->boolean('amount_round_up') ? '1' : '0']
        );

        return redirect()
            ->route('setting.index')
            ->with('success', 'Settings updated successfully.');
    }
}
