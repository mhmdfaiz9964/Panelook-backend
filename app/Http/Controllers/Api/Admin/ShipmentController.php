<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Shipment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ShipmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Shipment::with(['order.items', 'courier', 'shippingMethod'])->orderBy('id', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('courier_id')) {
            $query->where('courier_id', $request->courier_id);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('shipment_number', 'like', "%{$s}%")
                  ->orWhere('tracking_number', 'like', "%{$s}%")
                  ->orWhereHas('order', function ($oq) use ($s) {
                      $oq->where('order_number', 'like', "%{$s}%")
                         ->orWhere('customer_name', 'like', "%{$s}%")
                         ->orWhere('customer_phone', 'like', "%{$s}%");
                  });
            });
        }

        $shipments = $query->paginate(20);

        // Calculate KPI summary
        $kpis = [
            'total' => Shipment::count(),
            'pending' => Shipment::where('status', 'Pending')->count(),
            'ready_to_pack' => Shipment::where('status', 'Ready to Pack')->count(),
            'dispatched' => Shipment::where('status', 'Dispatched')->count(),
            'delivered' => Shipment::where('status', 'Delivered')->count(),
            'returned' => Shipment::where('status', 'Returned')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $shipments,
            'kpis' => $kpis,
        ]);
    }

    public function show($id)
    {
        $shipment = Shipment::with(['order.items', 'courier', 'shippingMethod', 'statusHistories.creator'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $shipment]);
    }

    public function assignCourier(Request $request, $id)
    {
        $request->validate([
            'courier_id' => 'required|integer|exists:couriers,id',
            'tracking_number' => 'required|string|max:100',
        ]);

        $shipment = Shipment::findOrFail($id);
        $courier = Courier::findOrFail($request->courier_id);

        $shipment->update([
            'courier_id' => $courier->id,
            'tracking_number' => $request->tracking_number,
            'status' => 'Dispatched',
            'dispatch_date' => now()->toDateString(),
        ]);

        // Sync back to order
        $shipment->order->update([
            'courier_name' => $courier->name,
            'tracking_number' => $request->tracking_number,
            'shipping_status' => 'Shipped',
            'order_status' => 'Shipped',
        ]);

        $shipment->statusHistories()->create([
            'status' => 'Dispatched',
            'comment' => "Assigned to {$courier->name} with tracking #{$request->tracking_number}",
            'created_by' => $request->user()?->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Courier assigned and shipment marked Dispatched',
            'data' => $shipment->load(['courier', 'order']),
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:Pending,Ready to Pack,Packed,Dispatched,In Transit,Delivered,Failed,Returned',
            'notes' => 'nullable|string',
        ]);

        $shipment = Shipment::findOrFail($id);
        $shipment->update([
            'status' => $request->status,
            'notes' => $request->notes ?? $shipment->notes,
            'delivered_date' => $request->status === 'Delivered' ? now()->toDateString() : $shipment->delivered_date,
        ]);

        if ($request->status === 'Delivered') {
            $shipment->order->update([
                'shipping_status' => 'Delivered',
                'order_status' => 'Delivered',
                'payment_status' => 'Paid', // For COD
            ]);
        }

        $shipment->statusHistories()->create([
            'status' => $request->status,
            'comment' => $request->notes ?? "Shipment status updated to {$request->status}",
            'created_by' => $request->user()?->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Shipment status updated to {$request->status}",
            'data' => $shipment,
        ]);
    }

    public function couriers()
    {
        return response()->json([
            'success' => true,
            'data' => Courier::where('status', true)->get(),
        ]);
    }

    public function shippingLabelPdf($id)
    {
        $shipment = Shipment::with(['order.items', 'courier', 'shippingMethod'])->findOrFail($id);
        $order = $shipment->order;
        $storeName = Setting::get('store_name', 'Panelook.lk');
        $storePhone = Setting::get('phone', '076 602 5870');

        $codText = $order->payment_method === 'COD'
            ? "<div style='font-size:16px; font-weight:bold; color:#dc2626; border:2px solid #dc2626; padding:8px; margin-top:10px; text-align:center;'>COLLECT CASH ON DELIVERY (COD): LKR " . number_format($order->grand_total, 2) . "</div>"
            : "<div style='font-size:14px; font-weight:bold; color:#16a34a; border:2px solid #16a34a; padding:8px; margin-top:10px; text-align:center;'>PREPAID PARCEL — DO NOT COLLECT CASH</div>";

        $html = "
        <html>
        <head>
            <style>
                body { font-family: sans-serif; margin: 0; padding: 15px; color: #111; }
                .label-box { border: 3px solid #000; padding: 15px; width: 100%; box-sizing: border-box; }
                .brand-header { border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
                .logo-text { font-size: 22px; font-weight: 900; color: #1e40af; }
                .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
                .meta-table td { vertical-align: top; padding: 4px 0; }
                .dest-box { background: #f8fafc; border: 2px dashed #334155; padding: 12px; margin-bottom: 15px; border-radius: 4px; }
                .barcode-sim { text-align: center; letter-spacing: 4px; font-family: monospace; font-size: 18px; font-weight: bold; background: #eee; padding: 8px; margin-top: 10px; }
            </style>
        </head>
        <body>
            <div class='label-box'>
                <div class='brand-header'>
                    <table style='width:100%;'>
                        <tr>
                            <td>
                                <div class='logo-text'>{$storeName}</div>
                                <div style='font-size:11px; font-weight:bold; color:#475569;'>PRIORITY LAPTOP SCREEN DISPATCH</div>
                                <div style='font-size:10px;'>Hotline: {$storePhone} | Web: panelook.lk</div>
                            </td>
                            <td style='text-align:right;'>
                                <div style='font-size:12px; font-weight:bold;'>COURIER: " . strtoupper($shipment->courier?->name ?? 'STANDARD COURIER') . "</div>
                                <div style='font-size:13px; font-weight:bold; color:#0f172a;'>TRACK: " . ($shipment->tracking_number ?? 'PENDING') . "</div>
                                <div style='font-size:11px;'>Order: <strong>{$order->order_number}</strong></div>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class='dest-box'>
                    <div style='font-size:11px; font-weight:bold; color:#64748b; text-transform:uppercase;'>Deliver To:</div>
                    <div style='font-size:16px; font-weight:900; color:#0f172a; margin: 3px 0;'>{$order->customer_name}</div>
                    <div style='font-size:14px; font-weight:bold; color:#1e293b;'>Phone: {$order->customer_phone}</div>
                    <div style='font-size:13px; margin-top: 4px;'>{$order->shipping_address}</div>
                    <div style='font-size:14px; font-weight:bold; color:#0f172a; margin-top: 2px;'>
                        {$order->city}, {$order->district}" . ($order->province ? " ({$order->province} Province)" : "") . "
                    </div>
                </div>

                {$codText}

                <div style='margin-top:15px; font-size:11px;'>
                    <strong>Package Contents:</strong> " . count($order->items) . "x Fragile Laptop LCD/LED Display Panel (Tested Genuine)<br>
                    <strong>Handling Instructions:</strong> FRAGILE — DO NOT BEND OR CRUSH — HANDLE WITH EXTREME CARE.
                </div>

                <div class='barcode-sim'>
                    * {$order->order_number} *
                </div>
            </div>
        </body>
        </html>";

        $pdf = Pdf::loadHTML($html);
        return $pdf->download("Label-{$order->order_number}.pdf");
    }
}
