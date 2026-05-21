<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SettingsRequest;
use App\Models\Setting;
use App\Models\UserActivityLog;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Settings/Index', [
            'settings' => Setting::getAll(),
        ]);
    }

    public function update(SettingsRequest $request)
    {
        $validated = $request->validated();

        foreach ($validated as $key => $value) {
            Setting::set($key, $value ?? '');
        }

        UserActivityLog::record(auth()->id(), 'settings_updated', 'Settings updated');

        return back();
    }
}
