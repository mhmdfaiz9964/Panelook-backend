<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\WhatsappOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WhatsappOrderController extends Controller
{
    public function index()
    {
        return response()->json(['success' => true, 'data' => WhatsappOrder::orderBy('id', 'desc')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:50',
            'product_inquiry' => 'required|string',
            'quantity' => 'integer|min:1',
            'amount' => 'nullable|numeric',
        ]);
        $data['status'] = 'New';
        return response()->json(['success' => true, 'data' => WhatsappOrder::create($data)], 201);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:New,Contacted,Confirmed,Processing,Completed,Cancelled']);
        $wo = WhatsappOrder::findOrFail($id);
        $wo->update(['status' => $request->status]);
        return response()->json(['success' => true, 'data' => $wo]);
    }

    public function convertToOrder(Request $request, $id)
    {
        $wo = WhatsappOrder::findOrFail($id);
        if ($wo->converted_order_id) {
            return response()->json(['success' => false, 'message' => 'Already converted.'], 422);
        }

        $data = $request->validate([
            'shipping_address' => 'required|string',
            'city' => 'required|string',
            'district' => 'required|string',
            'unit_price' => 'required|numeric|min:0',
        ]);

        $order = DB::transaction(function () use ($wo, $data) {
            $subtotal = $data['unit_price'] * $wo->quantity;
            $order = Order::create([
                'order_number' => 'ORD-' . strtoupper(Str::random(6)),
                'customer_name' => $wo->customer_name,
                'customer_email' => 'whatsapp@example.com',
                'customer_phone' => $wo->customer_phone,
                'shipping_address' => $data['shipping_address'],
                'city' => $data['city'],
                'district' => $data['district'],
                'payment_method' => 'COD',
                'payment_status' => 'Pending',
                'shipping_status' => 'Pending',
                'order_status' => 'Confirmed',
                'subtotal' => $subtotal,
                'shipping_cost' => 650,
                'discount' => 0,
                'grand_total' => $subtotal + 650,
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'product_name' => $wo->product_inquiry,
                'quantity' => $wo->quantity,
                'unit_price' => $data['unit_price'],
                'subtotal' => $subtotal,
            ]);

            $wo->update(['status' => 'Completed', 'converted_order_id' => $order->id]);

            return $order;
        });

        return response()->json(['success' => true, 'data' => $order->load('items')], 201);
    }

    public function destroy($id)
    {
        WhatsappOrder::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Entry deleted']);
    }
}
