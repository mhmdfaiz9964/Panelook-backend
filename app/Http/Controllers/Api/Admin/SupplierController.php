<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        return response()->json(['success' => true, 'data' => Supplier::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'status' => 'boolean',
        ]);
        return response()->json(['success' => true, 'data' => Supplier::create($data)], 201);
    }

    public function update(Request $request, $id)
    {
        $supplier = Supplier::findOrFail($id);
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'status' => 'boolean',
        ]);
        $supplier->update($data);
        return response()->json(['success' => true, 'data' => $supplier]);
    }

    public function destroy($id)
    {
        Supplier::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Supplier deleted']);
    }
}
