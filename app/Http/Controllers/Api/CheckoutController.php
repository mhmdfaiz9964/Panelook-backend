<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Setting;
use App\Models\Shipment;
use App\Models\ShippingMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function checkout(Request $request)
    {
        // 1. Validate Input
        $validator = Validator::make($request->all(), [
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:50',
            'whatsapp_phone' => 'nullable|string|max:50',
            'shipping_address' => 'required|string',
            'province' => 'nullable|string|max:100',
            'district' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
            'shipping_method_id' => 'nullable|integer|exists:shipping_methods,id',
            'shipping_method_code' => 'nullable|string',
            'payment_method' => 'required|string|in:COD,BankTransfer,Online,WhatsApp,PayHere,KOKO',
            'coupon_code' => 'nullable|string',
            'idempotency_key' => 'nullable|string|max:64',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.variation_id' => 'nullable|integer',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // 2. Idempotency Check: Prevent duplicate orders on network retries or double-clicks
        if ($request->filled('idempotency_key')) {
            $existing = Order::with('items')->where('idempotency_key', $request->idempotency_key)->first();
            if ($existing) {
                return response()->json([
                    'success' => true,
                    'message' => 'Order already processed (idempotent)',
                    'data' => [
                        'order' => $existing,
                        'whatsapp_url' => $this->buildWhatsAppUrl($existing),
                    ]
                ], 200);
            }
        }

        try {
            DB::beginTransaction();

            $subtotal = 0;
            $orderItemsData = [];

            // 3. Atomically lock & validate each product and stock in database
            foreach ($request->items as $item) {
                // Pessimistic locking prevents concurrent checkouts from overselling
                $product = Product::where('id', $item['product_id'])->lockForUpdate()->first();

                if (!$product || $product->status !== 'active') {
                    throw new \Exception("Product '{$item['product_id']}' is no longer available.");
                }

                $variation = null;
                $availableStock = $product->stock_quantity;
                $unitPrice = (float)$product->selling_price;

                if (!empty($item['variation_id'])) {
                    $variation = ProductVariation::where('id', $item['variation_id'])
                        ->where('product_id', $product->id)
                        ->lockForUpdate()
                        ->first();

                    if ($variation) {
                        $availableStock = $variation->stock_quantity;
                        $unitPrice = (float)$variation->price;
                    }
                }

                if ($availableStock < $item['quantity']) {
                    throw new \Exception("Insufficient stock for '{$product->name}'. Available: {$availableStock}, Requested: {$item['quantity']}.");
                }

                $itemSubtotal = $unitPrice * $item['quantity'];
                $subtotal += $itemSubtotal;

                // Deduct stock and record audit ledger
                if ($variation) {
                    $prevStock = $variation->stock_quantity;
                    $newStock = $prevStock - $item['quantity'];
                    $variation->update(['stock_quantity' => $newStock]);

                    InventoryTransaction::create([
                        'product_id' => $product->id,
                        'variation_id' => $variation->id,
                        'previous_quantity' => $prevStock,
                        'change_quantity' => -$item['quantity'],
                        'new_quantity' => $newStock,
                        'reason' => 'sale',
                        'user_id' => $request->user()?->id,
                    ]);
                } else {
                    $prevStock = $product->stock_quantity;
                    $newStock = $prevStock - $item['quantity'];
                    $product->update(['stock_quantity' => $newStock]);

                    InventoryTransaction::create([
                        'product_id' => $product->id,
                        'previous_quantity' => $prevStock,
                        'change_quantity' => -$item['quantity'],
                        'new_quantity' => $newStock,
                        'reason' => 'sale',
                        'user_id' => $request->user()?->id,
                    ]);
                }

                $orderItemsData[] = [
                    'product_id' => $product->id,
                    'variation_id' => $variation?->id,
                    'product_name' => $product->name,
                    'variation_details' => [
                        'panel_number' => $product->panel_number,
                        'display_size' => $product->display_size,
                        'pin_type' => $product->pin_type,
                        'touch_type' => $product->touch_type,
                        'display_type' => $product->display_type,
                        'resolution' => $product->resolution,
                        'warranty' => $product->warranty,
                    ],
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'subtotal' => $itemSubtotal,
                ];
            }

            // 4. Authoritative Shipping Fee Calculation
            $shippingMethod = null;
            if ($request->filled('shipping_method_id')) {
                $shippingMethod = ShippingMethod::find($request->shipping_method_id);
            } elseif ($request->filled('shipping_method_code')) {
                $shippingMethod = ShippingMethod::where('code', $request->shipping_method_code)->first();
            }

            if (!$shippingMethod) {
                $isColombo = strtolower($request->district) === 'colombo';
                $shippingMethod = ShippingMethod::where('code', $isColombo ? 'standard_colombo' : 'standard_islandwide')->first()
                    ?? ShippingMethod::first();
            }

            $shippingCost = (float)$shippingMethod->price;
            if ($shippingMethod->zone && $shippingMethod->zone->free_shipping_min && $subtotal >= (float)$shippingMethod->zone->free_shipping_min) {
                $shippingCost = 0.0;
            }

            // 5. Authoritative Coupon Discount Calculation
            $discount = 0.0;
            $appliedCouponCode = null;
            if ($request->filled('coupon_code')) {
                $coupon = Coupon::where('code', strtoupper(trim($request->coupon_code)))
                    ->where('status', true)
                    ->first();

                if ($coupon) {
                    $today = date('Y-m-d');
                    $validDates = (!$coupon->start_date || $coupon->start_date <= $today) && (!$coupon->end_date || $coupon->end_date >= $today);
                    $validUsage = (!$coupon->usage_limit || $coupon->used_count < $coupon->usage_limit);
                    $validMin = (!$coupon->min_order || $subtotal >= (float)$coupon->min_order);

                    if ($validDates && $validUsage && $validMin) {
                        if ($coupon->discount_type === 'percentage') {
                            $discount = ($subtotal * (float)$coupon->discount_value) / 100;
                            if ($coupon->max_discount && $discount > (float)$coupon->max_discount) {
                                $discount = (float)$coupon->max_discount;
                            }
                        } else {
                            $discount = min($subtotal, (float)$coupon->discount_value);
                        }
                        $discount = round($discount, 2);
                        $coupon->increment('used_count');
                        $appliedCouponCode = $coupon->code;
                    }
                }
            }

            $grandTotal = max(0, $subtotal + $shippingCost - $discount);

            // 6. Generate sequential, collision-free Order & Invoice numbers
            $datePrefix = date('Ymd');
            $countToday = Order::whereDate('created_at', date('Y-m-d'))->count() + 1;
            $orderNumber = 'ORD-' . $datePrefix . '-' . str_pad($countToday, 4, '0', STR_PAD_LEFT);
            $invoiceNumber = 'INV-' . $datePrefix . '-' . str_pad($countToday, 4, '0', STR_PAD_LEFT);

            // 7. Create Order Record
            $order = Order::create([
                'order_number' => $orderNumber,
                'invoice_number' => $invoiceNumber,
                'user_id' => $request->user()?->id,
                'customer_name' => $request->customer_name,
                'customer_email' => $request->customer_email,
                'customer_phone' => $request->customer_phone,
                'shipping_address' => $request->shipping_address,
                'province' => $request->province,
                'district' => $request->district,
                'city' => $request->city,
                'postal_code' => $request->postal_code,
                'shipping_method_id' => $shippingMethod->id,
                'shipping_method_name' => $shippingMethod->name,
                'notes' => $request->notes,
                'idempotency_key' => $request->idempotency_key,
                'coupon_code' => $appliedCouponCode,
                'payment_method' => $request->payment_method,
                'payment_status' => 'Pending',
                'shipping_status' => 'Pending',
                'order_status' => 'Pending',
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'discount' => $discount,
                'grand_total' => $grandTotal,
            ]);

            // 8. Create Order Items
            foreach ($orderItemsData as $oItem) {
                $order->items()->create($oItem);
            }

            // 9. Create Status History
            $order->statusHistories()->create([
                'status' => 'Pending',
                'comment' => 'Order placed successfully by customer',
                'created_by' => $request->user()?->id,
            ]);

            // 10. Automatically Create Linked Shipment Record
            $shipmentNumber = 'SHP-' . $datePrefix . '-' . str_pad($countToday, 4, '0', STR_PAD_LEFT);
            $shipment = Shipment::create([
                'shipment_number' => $shipmentNumber,
                'order_id' => $order->id,
                'shipping_method_id' => $shippingMethod->id,
                'shipping_fee' => $shippingCost,
                'status' => 'Pending',
                'notes' => 'Awaiting dispatch confirmation from warehouse',
            ]);

            $shipment->statusHistories()->create([
                'status' => 'Pending',
                'comment' => 'Shipment initialised for order',
                'created_by' => $request->user()?->id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully',
                'data' => [
                    'order' => $order->load(['items', 'shipment.shippingMethod']),
                    'whatsapp_url' => $this->buildWhatsAppUrl($order),
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Order placement failed: ' . $e->getMessage(),
            ], 400);
        }
    }

    private function buildWhatsAppUrl(Order $order): string
    {
        $waPhone = Setting::get('whatsapp', '94766025870');
        $itemsSummary = $order->items->map(fn ($i) => "• {$i->product_name} x {$i->quantity}")->join("\n");
        $waMessage = "Hello Panelook.lk,\n\nI placed order *#{$order->order_number}*.\n*Customer:* {$order->customer_name}\n*Phone:* {$order->customer_phone}\n*Items:*\n{$itemsSummary}\n*Total:* LKR " . number_format($order->grand_total, 2) . "\n*Delivery:* {$order->shipping_address}, {$order->city}, {$order->district}\n*Payment:* {$order->payment_method}";

        return "https://wa.me/{$waPhone}?text=" . urlencode($waMessage);
    }
}
