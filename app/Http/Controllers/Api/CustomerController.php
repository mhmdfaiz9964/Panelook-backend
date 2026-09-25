<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    public function profile(Request $request)
    {
        $user = $request->user();
        $ordersCount = Order::where('user_id', $user->id)->count();
        $totalSpent = Order::where('user_id', $user->id)->where('order_status', '!=', 'Cancelled')->sum('grand_total');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'role' => $user->role,
                'created_at' => $user->created_at->format('M d, Y'),
                'stats' => [
                    'orders_count' => $ordersCount,
                    'total_spent' => $totalSpent,
                ],
            ],
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user->update($request->only('name', 'phone', 'address'));

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user,
        ]);
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Current password is incorrect.'], 422);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ]);
    }

    public function orders(Request $request)
    {
        $user = $request->user();
        $orders = Order::with(['items', 'shipment.courier', 'shippingMethod'])
            ->where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    public function orderDetail(Request $request, $id)
    {
        $user = $request->user();
        $order = Order::with(['items', 'shipment.courier', 'shipment.statusHistories', 'statusHistories', 'shippingMethod'])
            ->where('user_id', $user->id)
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('order_number', $id);
            })
            ->first();

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    public function addresses(Request $request)
    {
        $addresses = UserAddress::with(['province', 'district', 'city'])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $addresses,
        ]);
    }

    public function storeAddress(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|string|in:Home,Work,Other',
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'province_id' => 'nullable|integer|exists:provinces,id',
            'district_id' => 'nullable|integer|exists:districts,id',
            'city_id' => 'nullable|integer|exists:cities,id',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'delivery_instructions' => 'nullable|string',
            'is_default' => 'boolean',
        ]);

        if (!empty($data['is_default'])) {
            UserAddress::where('user_id', $request->user()->id)->update(['is_default' => false]);
        }

        $data['user_id'] = $request->user()->id;
        $address = UserAddress::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Address saved successfully',
            'data' => $address->load(['province', 'district', 'city']),
        ], 201);
    }

    public function destroyAddress(Request $request, $id)
    {
        $address = UserAddress::where('user_id', $request->user()->id)->findOrFail($id);
        $address->delete();

        return response()->json([
            'success' => true,
            'message' => 'Address deleted successfully',
        ]);
    }
}
