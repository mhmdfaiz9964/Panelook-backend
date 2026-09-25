<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index()
    {
        return response()->json(['success' => true, 'data' => Coupon::orderBy('id', 'desc')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric',
            'min_order' => 'nullable|numeric',
            'max_discount' => 'nullable|numeric',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'usage_limit' => 'nullable|integer',
            'per_customer_limit' => 'nullable|integer',
            'status' => 'boolean',
        ]);
        $data['code'] = strtoupper($data['code']);
        return response()->json(['success' => true, 'data' => Coupon::create($data)], 201);
    }

    public function update(Request $request, $id)
    {
        $coupon = Coupon::findOrFail($id);
        $data = $request->validate([
            'code' => 'sometimes|string|max:50|unique:coupons,code,' . $id,
            'discount_type' => 'sometimes|in:percentage,fixed',
            'discount_value' => 'sometimes|numeric',
            'min_order' => 'nullable|numeric',
            'max_discount' => 'nullable|numeric',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'usage_limit' => 'nullable|integer',
            'per_customer_limit' => 'nullable|integer',
            'status' => 'boolean',
        ]);
        if (isset($data['code'])) $data['code'] = strtoupper($data['code']);
        $coupon->update($data);
        return response()->json(['success' => true, 'data' => $coupon]);
    }

    public function destroy($id)
    {
        Coupon::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Coupon deleted']);
    }
}
