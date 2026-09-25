<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Coupon;
use App\Models\District;
use App\Models\Province;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function provinces()
    {
        $provinces = Province::orderBy('sort_order')->orderBy('name')->get();
        return response()->json([
            'success' => true,
            'data' => $provinces,
        ]);
    }

    public function districts(Request $request)
    {
        $query = District::with('province')->orderBy('name');

        if ($request->filled('province_id')) {
            $query->where('province_id', $request->province_id);
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(),
        ]);
    }

    public function cities(Request $request)
    {
        $query = City::with('district.province')->orderBy('name');

        if ($request->filled('district_id')) {
            $query->where('district_id', $request->district_id);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        return response()->json([
            'success' => true,
            'data' => $query->limit(100)->get(),
        ]);
    }

    public function shippingMethods(Request $request)
    {
        $districtName = $request->get('district', '');
        $isColombo = strtolower($districtName) === 'colombo';

        $methods = ShippingMethod::where('status', true)->get();

        // Custom filtering or marking for Colombo vs Outstation
        $formatted = $methods->map(function ($m) use ($isColombo) {
            $applicable = true;
            if ($m->code === 'express_colombo' && !$isColombo) {
                $applicable = false;
            }
            return [
                'id' => $m->id,
                'code' => $m->code,
                'name' => $m->name,
                'description' => $m->description,
                'price' => (float)$m->price,
                'estimated_days' => $m->estimated_days,
                'cod_supported' => (bool)$m->cod_supported,
                'is_applicable' => $applicable,
            ];
        })->filter(fn ($m) => $m['is_applicable'])->values();

        return response()->json([
            'success' => true,
            'data' => $formatted,
        ]);
    }

    public function calculateShipping(Request $request)
    {
        $request->validate([
            'shipping_method_id' => 'nullable|integer',
            'shipping_method_code' => 'nullable|string',
            'district' => 'nullable|string',
            'city' => 'nullable|string',
            'subtotal' => 'nullable|numeric|min:0',
        ]);

        $subtotal = (float)$request->get('subtotal', 0);
        $method = null;

        if ($request->filled('shipping_method_id')) {
            $method = ShippingMethod::find($request->shipping_method_id);
        } elseif ($request->filled('shipping_method_code')) {
            $method = ShippingMethod::where('code', $request->shipping_method_code)->first();
        }

        if (!$method) {
            // Default based on district
            $isColombo = strtolower($request->get('district', '')) === 'colombo';
            $methodCode = $isColombo ? 'standard_colombo' : 'standard_islandwide';
            $method = ShippingMethod::where('code', $methodCode)->first() ?? ShippingMethod::first();
        }

        $fee = (float)$method->price;

        // Check for free shipping thresholds if zone configured
        if ($method->zone && $method->zone->free_shipping_min && $subtotal >= (float)$method->zone->free_shipping_min) {
            $fee = 0.0;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'shipping_method_id' => $method->id,
                'shipping_method_name' => $method->name,
                'shipping_method_code' => $method->code,
                'shipping_fee' => $fee,
                'estimated_days' => $method->estimated_days,
                'cod_supported' => (bool)$method->cod_supported,
            ],
        ]);
    }

    public function validateCoupon(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'subtotal' => 'required|numeric|min:0',
        ]);

        $subtotal = (float)$request->subtotal;
        $coupon = Coupon::where('code', strtoupper(trim($request->code)))
            ->where('status', true)
            ->first();

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired coupon code.',
            ], 422);
        }

        $today = date('Y-m-d');
        if ($coupon->start_date && $coupon->start_date > $today) {
            return response()->json(['success' => false, 'message' => 'Coupon is not yet active.'], 422);
        }
        if ($coupon->end_date && $coupon->end_date < $today) {
            return response()->json(['success' => false, 'message' => 'Coupon has expired.'], 422);
        }
        if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
            return response()->json(['success' => false, 'message' => 'Coupon usage limit has been reached.'], 422);
        }
        if ($coupon->min_order && $subtotal < (float)$coupon->min_order) {
            return response()->json([
                'success' => false,
                'message' => 'Minimum order amount of LKR ' . number_format($coupon->min_order, 2) . ' required.',
            ], 422);
        }

        $discount = 0;
        if ($coupon->discount_type === 'percentage') {
            $discount = ($subtotal * (float)$coupon->discount_value) / 100;
            if ($coupon->max_discount && $discount > (float)$coupon->max_discount) {
                $discount = (float)$coupon->max_discount;
            }
        } else {
            $discount = min($subtotal, (float)$coupon->discount_value);
        }

        return response()->json([
            'success' => true,
            'message' => 'Coupon applied successfully!',
            'data' => [
                'code' => $coupon->code,
                'discount' => round($discount, 2),
                'discount_type' => $coupon->discount_type,
                'discount_value' => (float)$coupon->discount_value,
            ],
        ]);
    }
}
