<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CouponRequest;
use App\Models\Coupon;
use App\Models\UserActivityLog;
use Inertia\Inertia;

class CouponController extends Controller
{
    public function index()
    {
        $coupons = Coupon::orderByDesc('created_at')->paginate(20);

        return Inertia::render('Admin/Coupons/Index', [
            'coupons' => $coupons,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Coupons/Create');
    }

    public function store(CouponRequest $request)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        $data['used_count'] = 0;

        $coupon = Coupon::create($data);

        UserActivityLog::record(auth()->id(), 'coupon_created', "Coupon created: {$coupon->code}");

        return redirect()->route('admin.coupons')
            ->with('success', 'Coupon created successfully.');
    }

    public function edit(Coupon $coupon)
    {
        return Inertia::render('Admin/Coupons/Edit', [
            'coupon' => $coupon,
        ]);
    }

    public function update(CouponRequest $request, Coupon $coupon)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $coupon->update($data);

        UserActivityLog::record(auth()->id(), 'coupon_updated', "Coupon updated: {$coupon->code}");

        return redirect()->route('admin.coupons')
            ->with('success', 'Coupon updated successfully.');
    }

    public function destroy(Coupon $coupon)
    {
        UserActivityLog::record(auth()->id(), 'coupon_deleted', "Coupon deleted: {$coupon->code}");

        $coupon->delete();

        return redirect()->route('admin.coupons')
            ->with('success', 'Coupon deleted successfully.');
    }
}
