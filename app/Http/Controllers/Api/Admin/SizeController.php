<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Size;
use Illuminate\Http\Request;

class SizeController extends Controller
{
    public function index()
    {
        return response()->json(['success' => true, 'data' => Size::orderBy('sort_order')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'label' => 'required|string|max:50|unique:sizes,label',
            'sort_order' => 'integer',
            'status' => 'boolean',
        ]);
        return response()->json(['success' => true, 'data' => Size::create($data)], 201);
    }

    public function update(Request $request, $id)
    {
        $size = Size::findOrFail($id);
        $data = $request->validate([
            'label' => 'sometimes|string|max:50|unique:sizes,label,' . $id,
            'sort_order' => 'integer',
            'status' => 'boolean',
        ]);
        $size->update($data);
        return response()->json(['success' => true, 'data' => $size]);
    }

    public function destroy($id)
    {
        Size::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Size deleted']);
    }
}
