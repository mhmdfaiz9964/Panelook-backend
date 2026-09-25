<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $orders = Order::with('items')
            ->where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    public function show($id, Request $request)
    {
        $order = Order::with(['items', 'statusHistories'])->where('id', $id)->orWhere('order_number', $id)->first();

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    public function invoicePdf($id)
    {
        $order = Order::with('items')->where('id', $id)->orWhere('order_number', $id)->firstOrFail();
        $storeName = Setting::get('store_name', 'Panelook.lk');
        $storePhone = Setting::get('phone', '076 123 4567');
        $storeEmail = Setting::get('email', 'info@panelook.lk');

        $html = "
        <html>
        <head>
            <style>
                body { font-family: sans-serif; font-size: 13px; color: #333; margin: 0; padding: 20px; }
                .header { border-bottom: 2px solid #2563eb; padding-bottom: 15px; margin-bottom: 20px; }
                .header table { width: 100%; }
                .title { font-size: 24px; font-weight: bold; color: #2563eb; }
                .meta { margin-bottom: 20px; width: 100%; }
                .meta td { vertical-align: top; width: 50%; }
                .table { width: 100%; border-collapse: collapse; margin-top: 15px; }
                .table th { background: #f1f5f9; padding: 10px; text-align: left; border-bottom: 1px solid #cbd5e1; }
                .table td { padding: 10px; border-bottom: 1px solid #e2e8f0; }
                .totals { float: right; width: 250px; margin-top: 20px; }
                .totals table { width: 100%; border-collapse: collapse; }
                .totals td { padding: 5px 0; text-align: right; }
                .totals .grand { font-size: 16px; font-weight: bold; color: #2563eb; border-top: 2px solid #2563eb; padding-top: 8px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <table>
                    <tr>
                        <td>
                            <div class='title'>{$storeName}</div>
                            <div>Sri Lanka's #1 Laptop Display Store</div>
                            <div>Phone: {$storePhone} | Email: {$storeEmail}</div>
                        </td>
                        <td style='text-align: right;'>
                            <h2 style='margin:0; color:#0f172a;'>INVOICE</h2>
                            <div>Invoice #: <strong>{$order->invoice_number}</strong></div>
                            <div>Order #: <strong>{$order->order_number}</strong></div>
                            <div>Date: " . $order->created_at->format('d M Y') . "</div>
                        </td>
                    </tr>
                </table>
            </div>

            <table class='meta'>
                <tr>
                    <td>
                        <strong>Billed To:</strong><br>
                        {$order->customer_name}<br>
                        Phone: {$order->customer_phone}<br>
                        Email: {$order->customer_email}<br>
                        Address: {$order->shipping_address}, {$order->city}, {$order->district}
                    </td>
                    <td>
                        <strong>Payment Info:</strong><br>
                        Method: {$order->payment_method}<br>
                        Status: <strong>{$order->payment_status}</strong><br>
                        Shipping Status: <strong>{$order->shipping_status}</strong>
                    </td>
                </tr>
            </table>

            <table class='table'>
                <thead>
                    <tr>
                        <th>Item Description</th>
                        <th>Qty</th>
                        <th>Unit Price (LKR)</th>
                        <th>Subtotal (LKR)</th>
                    </tr>
                </thead>
                <tbody>";

        foreach ($order->items as $item) {
            $details = '';
            if (!empty($item->variation_details)) {
                $v = $item->variation_details;
                $vSize = $v['display_size'] ?? '';
                $vPin = $v['pin_type'] ?? '';
                $vPanel = $v['panel_number'] ?? '';
                $details = " (Size: {$vSize}, Pin: {$vPin}, Panel: {$vPanel})";
            }
            $html .= "
                    <tr>
                        <td><strong>{$item->product_name}</strong><br><small>{$details}</small></td>
                        <td>{$item->quantity}</td>
                        <td>" . number_format($item->unit_price, 2) . "</td>
                        <td>" . number_format($item->subtotal, 2) . "</td>
                    </tr>";
        }

        $html .= "
                </tbody>
            </table>

            <div class='totals'>
                <table>
                    <tr>
                        <td>Subtotal:</td>
                        <td>LKR " . number_format($order->subtotal, 2) . "</td>
                    </tr>
                    <tr>
                        <td>Shipping:</td>
                        <td>LKR " . number_format($order->shipping_cost, 2) . "</td>
                    </tr>
                    <tr class='grand'>
                        <td>Grand Total:</td>
                        <td>LKR " . number_format($order->grand_total, 2) . "</td>
                    </tr>
                </table>
            </div>
        </body>
        </html>";

        $pdf = Pdf::loadHTML($html);
        return $pdf->download("Invoice-{$order->order_number}.pdf");
    }

    public function shippingNotePdf($id)
    {
        $order = Order::with('items')->where('id', $id)->orWhere('order_number', $id)->firstOrFail();
        $storeName = Setting::get('store_name', 'Panelook.lk');

        $html = "
        <html>
        <head>
            <style>
                body { font-family: sans-serif; font-size: 13px; color: #333; padding: 20px; }
                .header { border-bottom: 2px dashed #000; padding-bottom: 10px; margin-bottom: 20px; }
                .box { border: 2px solid #000; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
                .table { width: 100%; border-collapse: collapse; margin-top: 15px; }
                .table th, .table td { border: 1px solid #ccc; padding: 8px; text-align: left; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>{$storeName} — SHIPPING NOTE / PACKING SLIP</h2>
                <div>Order #: <strong>{$order->order_number}</strong> | Date: " . $order->created_at->format('d M Y') . "</div>
            </div>

            <div class='box'>
                <h3>DELIVERY DESTINATION</h3>
                <div><strong>Recipient:</strong> {$order->customer_name}</div>
                <div><strong>Phone:</strong> {$order->customer_phone}</div>
                <div><strong>Address:</strong> {$order->shipping_address}</div>
                <div><strong>City / District:</strong> {$order->city}, {$order->district}</div>
                <div><strong>Payment Method:</strong> {$order->payment_method} (" . ($order->payment_method === 'COD' ? 'COLLECT CASH ON DELIVERY: LKR ' . number_format($order->grand_total, 2) : 'PREPAID') . ")</div>
            </div>

            <h3>ITEMS IN PACKAGE</h3>
            <table class='table'>
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Qty</th>
                        <th>Check Box</th>
                    </tr>
                </thead>
                <tbody>";

        foreach ($order->items as $item) {
            $html .= "
                    <tr>
                        <td>{$item->product_name}</td>
                        <td>{$item->quantity}</td>
                        <td>[  ] Verified</td>
                    </tr>";
        }

        $html .= "
                </tbody>
            </table>
        </body>
        </html>";

        $pdf = Pdf::loadHTML($html);
        return $pdf->download("ShippingNote-{$order->order_number}.pdf");
    }
}
