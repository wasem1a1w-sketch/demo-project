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
            'stripe_key' => ['nullable', 'string'],
            'stripe_secret' => ['nullable', 'string'],
            'stripe_webhook_secret' => ['nullable', 'string'],
            'paypal_client_id' => ['nullable', 'string'],
            'paypal_secret' => ['nullable', 'string'],
            'paypal_mode' => ['nullable', 'in:sandbox,live'],
        ]);

        Setting::set('shipping_rate', $validated['shipping_rate']);
        Setting::set('free_shipping_threshold', $validated['free_shipping_threshold']);
        Setting::set('tax_rate', $validated['tax_rate']);

        Setting::set('stripe_key', $validated['stripe_key'] ?? '');
        Setting::set('stripe_secret', $validated['stripe_secret'] ?? '');
        Setting::set('stripe_webhook_secret', $validated['stripe_webhook_secret'] ?? '');
        Setting::set('paypal_client_id', $validated['paypal_client_id'] ?? '');
        Setting::set('paypal_secret', $validated['paypal_secret'] ?? '');
        Setting::set('paypal_mode', $validated['paypal_mode'] ?? 'sandbox');

        UserActivityLog::record(auth()->id(), 'settings_updated', 'Settings updated');

        return back();
    }
}
