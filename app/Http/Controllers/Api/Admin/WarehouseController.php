<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index()
    {
        return response()->json(['success' => true, 'data' => Warehouse::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:warehouses,code',
            'address' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'status' => 'boolean',
        ]);
        return response()->json(['success' => true, 'data' => Warehouse::create($data)], 201);
    }

    public function update(Request $request, $id)
    {
        $warehouse = Warehouse::findOrFail($id);
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:50|unique:warehouses,code,' . $id,
            'address' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'status' => 'boolean',
        ]);
        $warehouse->update($data);
        return response()->json(['success' => true, 'data' => $warehouse]);
    }

    public function destroy($id)
    {
        Warehouse::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Warehouse deleted']);
    }
}
