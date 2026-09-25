<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReceiptItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        $orders = PurchaseOrder::with(['supplier', 'warehouse', 'items.product'])->orderBy('id', 'desc')->get();
        return response()->json(['success' => true, 'data' => $orders]);
    }

    public function show($id)
    {
        $order = PurchaseOrder::with(['supplier', 'warehouse', 'items.product', 'receipts.items'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $order]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'order_date' => 'required|date',
            'expected_date' => 'nullable|date',
            'discount' => 'nullable|numeric',
            'tax' => 'nullable|numeric',
            'shipping' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        $order = DB::transaction(function () use ($data, $request) {
            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $subtotal += $item['quantity'] * $item['unit_cost'];
            }
            $discount = $data['discount'] ?? 0;
            $tax = $data['tax'] ?? 0;
            $shipping = $data['shipping'] ?? 0;

            $po = PurchaseOrder::create([
                'po_number' => 'PO-' . strtoupper(Str::random(6)),
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'order_date' => $data['order_date'],
                'expected_date' => $data['expected_date'] ?? null,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'shipping' => $shipping,
                'total' => $subtotal - $discount + $tax + $shipping,
                'status' => 'Draft',
                'notes' => $data['notes'] ?? null,
                'created_by' => $request->user()?->id,
            ]);

            foreach ($data['items'] as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'subtotal' => $item['quantity'] * $item['unit_cost'],
                ]);
            }

            return $po;
        });

        return response()->json(['success' => true, 'data' => $order->load(['supplier', 'warehouse', 'items.product'])], 201);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:Draft,Ordered,Partially Received,Received,Cancelled']);
        $order = PurchaseOrder::findOrFail($id);
        $order->update(['status' => $request->status]);
        return response()->json(['success' => true, 'data' => $order]);
    }

    public function receive(Request $request, $id)
    {
        $order = PurchaseOrder::with('items')->findOrFail($id);
        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.purchase_order_item_id' => 'required|integer|exists:purchase_order_items,id',
            'items.*.received_quantity' => 'required|integer|min:0',
        ]);

        $receipt = DB::transaction(function () use ($order, $data, $request) {
            $receipt = PurchaseReceipt::create([
                'receipt_number' => 'RCPT-' . strtoupper(Str::random(6)),
                'purchase_order_id' => $order->id,
                'received_date' => now()->toDateString(),
                'received_by' => $request->user()?->id,
            ]);

            foreach ($data['items'] as $itemData) {
                if ($itemData['received_quantity'] <= 0) continue;

                $poItem = PurchaseOrderItem::findOrFail($itemData['purchase_order_item_id']);

                PurchaseReceiptItem::create([
                    'purchase_receipt_id' => $receipt->id,
                    'purchase_order_item_id' => $poItem->id,
                    'received_quantity' => $itemData['received_quantity'],
                ]);

                $poItem->increment('received_quantity', $itemData['received_quantity']);

                $product = Product::find($poItem->product_id);
                if ($product) {
                    $prevStock = $product->stock_quantity;
                    $newStock = $prevStock + $itemData['received_quantity'];
                    $product->update(['stock_quantity' => $newStock]);

                    InventoryTransaction::create([
                        'product_id' => $product->id,
                        'previous_quantity' => $prevStock,
                        'change_quantity' => $itemData['received_quantity'],
                        'new_quantity' => $newStock,
                        'reason' => 'stock_in',
                        'user_id' => $request->user()?->id,
                    ]);
                }
            }

            $order->refresh();
            $allReceived = $order->items->every(fn ($i) => $i->fresh()->received_quantity >= $i->quantity);
            $anyReceived = $order->items->contains(fn ($i) => $i->fresh()->received_quantity > 0);
            $order->update(['status' => $allReceived ? 'Received' : ($anyReceived ? 'Partially Received' : $order->status)]);

            return $receipt;
        });

        return response()->json(['success' => true, 'data' => $receipt->load('items')], 201);
    }

    public function destroy($id)
    {
        PurchaseOrder::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Purchase order deleted']);
    }

    public function receiptsIndex()
    {
        $receipts = PurchaseReceipt::with(['purchaseOrder.supplier', 'items.purchaseOrderItem.product', 'receiver'])
            ->orderBy('id', 'desc')->get();
        return response()->json(['success' => true, 'data' => $receipts]);
    }
}
