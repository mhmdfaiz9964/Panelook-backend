<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use Illuminate\Http\Request;

class AttributeController extends Controller
{
    public function index()
    {
        return response()->json(['success' => true, 'data' => Attribute::orderBy('sort_order')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:text,number,select,multiselect,boolean',
            'values' => 'nullable|array',
            'required' => 'boolean',
            'filterable' => 'boolean',
            'sort_order' => 'integer',
            'status' => 'boolean',
        ]);
        return response()->json(['success' => true, 'data' => Attribute::create($data)], 201);
    }

    public function update(Request $request, $id)
    {
        $attribute = Attribute::findOrFail($id);
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:text,number,select,multiselect,boolean',
            'values' => 'nullable|array',
            'required' => 'boolean',
            'filterable' => 'boolean',
            'sort_order' => 'integer',
            'status' => 'boolean',
        ]);
        $attribute->update($data);
        return response()->json(['success' => true, 'data' => $attribute]);
    }

    public function destroy($id)
    {
        Attribute::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Attribute deleted']);
    }
}
