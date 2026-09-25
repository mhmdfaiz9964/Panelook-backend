<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PanelPin;
use Illuminate\Http\Request;

class PanelPinController extends Controller
{
    public function index()
    {
        return response()->json(['success' => true, 'data' => PanelPin::orderBy('sort_order')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:50|unique:panel_pins,name',
            'pin_count' => 'nullable|integer',
            'description' => 'nullable|string',
            'sort_order' => 'integer',
            'status' => 'boolean',
        ]);
        return response()->json(['success' => true, 'data' => PanelPin::create($data)], 201);
    }

    public function update(Request $request, $id)
    {
        $pin = PanelPin::findOrFail($id);
        $data = $request->validate([
            'name' => 'sometimes|string|max:50|unique:panel_pins,name,' . $id,
            'pin_count' => 'nullable|integer',
            'description' => 'nullable|string',
            'sort_order' => 'integer',
            'status' => 'boolean',
        ]);
        $pin->update($data);
        return response()->json(['success' => true, 'data' => $pin]);
    }

    public function destroy($id)
    {
        PanelPin::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Panel pin deleted']);
    }
}
