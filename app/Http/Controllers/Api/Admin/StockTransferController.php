<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StockTransferController extends Controller
{
    public function index()
    {
        $transfers = StockTransfer::with(['product', 'sourceWarehouse', 'destinationWarehouse'])
            ->orderBy('id', 'desc')->get();
        return response()->json(['success' => true, 'data' => $transfers]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'source_warehouse_id' => 'required|integer|exists:warehouses,id',
            'destination_warehouse_id' => 'required|integer|different:source_warehouse_id|exists:warehouses,id',
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);
        $data['reference'] = 'TRF-' . strtoupper(Str::random(6));
        $data['status'] = 'Draft';
        $data['created_by'] = $request->user()?->id;

        $transfer = StockTransfer::create($data);
        return response()->json(['success' => true, 'data' => $transfer->load(['product', 'sourceWarehouse', 'destinationWarehouse'])], 201);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:Draft,Requested,Approved,In Transit,Completed,Cancelled']);
        $transfer = StockTransfer::findOrFail($id);
        $transfer->update(['status' => $request->status]);
        return response()->json(['success' => true, 'data' => $transfer]);
    }

    public function destroy($id)
    {
        StockTransfer::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Transfer deleted']);
    }
}
