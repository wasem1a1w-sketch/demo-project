<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\UserActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Settings/Index', [
            'settings' => Setting::getAll(),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'shipping_rate' => ['required', 'numeric', 'min:0'],
            'free_shipping_threshold' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        Setting::set('shipping_rate', $validated['shipping_rate']);
        Setting::set('free_shipping_threshold', $validated['free_shipping_threshold']);
        Setting::set('tax_rate', $validated['tax_rate']);

        UserActivityLog::record(auth()->id(), 'settings_updated', 'Settings updated');

        return back();
    }
}
