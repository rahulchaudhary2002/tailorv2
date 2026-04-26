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

        return redirect()
            ->route('setting.index')
            ->with('success', 'Settings updated successfully.');
    }
}
