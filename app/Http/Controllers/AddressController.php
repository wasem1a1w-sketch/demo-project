<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddressRequest;
use App\Models\Address;
use App\Models\UserActivityLog;
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

    public function store(AddressRequest $request)
    {
        $validated = $request->validated();
        $validated['user_id'] = auth()->id();

        if (!empty($validated['is_default'])) {
            auth()->user()->addresses()->where('type', $validated['type'])->update(['is_default' => false]);
        }

        Address::create($validated);

        UserActivityLog::record(auth()->id(), 'address_created', "Address created: {$validated['address1']}, {$validated['city']}");

        return back();
    }

    public function update(AddressRequest $request, $id)
    {
        $address = auth()->user()->addresses()->findOrFail($id);
        $validated = $request->validated();

        if (!empty($validated['is_default'])) {
            auth()->user()->addresses()
                ->where('type', $validated['type'])
                ->where('id', '!=', $id)
                ->update(['is_default' => false]);
        }

        $address->update($validated);

        UserActivityLog::record(auth()->id(), 'address_updated', "Address updated: {$validated['address1']}, {$validated['city']}");

        return back();
    }

    public function destroy($id)
    {
        $address = auth()->user()->addresses()->findOrFail($id);

        UserActivityLog::record(auth()->id(), 'address_deleted', "Address deleted: {$address->address1}, {$address->city}");

        $address->delete();

        return back();
    }
}
