<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuoteController extends Controller
{
    public function index()
    {
        return response()->json(['success' => true, 'data' => Quote::with('items')->orderBy('id', 'desc')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'customer_email' => 'nullable|email|max:255',
            'discount' => 'nullable|numeric',
            'shipping' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'expiry_date' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|integer|exists:products,id',
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $quote = DB::transaction(function () use ($data) {
            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
            }
            $discount = $data['discount'] ?? 0;
            $shipping = $data['shipping'] ?? 0;

            $quote = Quote::create([
                'quote_number' => 'QT-' . strtoupper(Str::random(6)),
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'] ?? null,
                'customer_email' => $data['customer_email'] ?? null,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'shipping' => $shipping,
                'total' => $subtotal - $discount + $shipping,
                'notes' => $data['notes'] ?? null,
                'expiry_date' => $data['expiry_date'] ?? null,
                'status' => 'Draft',
            ]);

            foreach ($data['items'] as $item) {
                QuoteItem::create([
                    'quote_id' => $quote->id,
                    'product_id' => $item['product_id'] ?? null,
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            return $quote;
        });

        return response()->json(['success' => true, 'data' => $quote->load('items')], 201);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:Draft,Sent,Accepted,Expired,Converted']);
        $quote = Quote::findOrFail($id);
        $quote->update(['status' => $request->status]);
        return response()->json(['success' => true, 'data' => $quote]);
    }

    public function convertToOrder(Request $request, $id)
    {
        $quote = Quote::with('items')->findOrFail($id);

        if ($quote->converted_order_id) {
            return response()->json(['success' => false, 'message' => 'Quote already converted.'], 422);
        }

        $data = $request->validate([
            'shipping_address' => 'required|string',
            'city' => 'required|string',
            'district' => 'required|string',
        ]);

        $order = DB::transaction(function () use ($quote, $data) {
            $order = Order::create([
                'order_number' => 'ORD-' . strtoupper(Str::random(6)),
                'customer_name' => $quote->customer_name,
                'customer_email' => $quote->customer_email ?? 'unknown@example.com',
                'customer_phone' => $quote->customer_phone ?? '',
                'shipping_address' => $data['shipping_address'],
                'city' => $data['city'],
                'district' => $data['district'],
                'payment_method' => 'COD',
                'payment_status' => 'Pending',
                'shipping_status' => 'Pending',
                'order_status' => 'Confirmed',
                'subtotal' => $quote->subtotal,
                'shipping_cost' => $quote->shipping,
                'discount' => $quote->discount,
                'grand_total' => $quote->total,
            ]);

            foreach ($quote->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                ]);
            }

            $quote->update(['status' => 'Converted', 'converted_order_id' => $order->id]);

            return $order;
        });

        return response()->json(['success' => true, 'data' => $order->load('items')], 201);
    }

    public function destroy($id)
    {
        Quote::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Quote deleted']);
    }
}
