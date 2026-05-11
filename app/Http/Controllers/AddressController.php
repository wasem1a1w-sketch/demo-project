<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index(Request $request)
    {
        $addresses = auth()->user()->addresses()->orderByDesc('is_default')->orderByDesc('id')->get();

        if (!$request->inertia() && $request->wantsJson()) {
            return response()->json(['addresses' => $addresses]);
        }

        return inertia('User/Addresses/Index', [
            'addresses' => $addresses,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:shipping,billing'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'address1' => ['required', 'string', 'max:500'],
            'address2' => ['nullable', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'is_default' => ['boolean'],
        ]);

        $validated['user_id'] = auth()->id();

        if (!empty($validated['is_default'])) {
            auth()->user()->addresses()->where('type', $validated['type'])->update(['is_default' => false]);
        }

        Address::create($validated);

        return back();
    }

    public function update(Request $request, $id)
    {
        $address = auth()->user()->addresses()->findOrFail($id);

        $validated = $request->validate([
            'type' => ['required', 'in:shipping,billing'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'address1' => ['required', 'string', 'max:500'],
            'address2' => ['nullable', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'is_default' => ['boolean'],
        ]);

        if (!empty($validated['is_default'])) {
            auth()->user()->addresses()->where('type', $validated['type'])->where('id', '!=', $id)->update(['is_default' => false]);
        }

        $address->update($validated);

        return back();
    }

    public function destroy($id)
    {
        auth()->user()->addresses()->findOrFail($id)->delete();
        return back();
    }
}
