<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductModel;
use Illuminate\Http\Request;

class ProductModelController extends Controller
{
    public function index()
    {
        return response()->json(['success' => true, 'data' => ProductModel::with('brand')->orderBy('model_name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'brand_id' => 'nullable|integer|exists:brands,id',
            'model_name' => 'required|string|max:255',
            'model_number' => 'nullable|string|max:255',
            'series' => 'nullable|string|max:255',
            'compatible_sizes' => 'nullable|array',
            'status' => 'boolean',
        ]);
        $model = ProductModel::create($data);
        return response()->json(['success' => true, 'data' => $model->load('brand')], 201);
    }

    public function update(Request $request, $id)
    {
        $model = ProductModel::findOrFail($id);
        $data = $request->validate([
            'brand_id' => 'nullable|integer|exists:brands,id',
            'model_name' => 'sometimes|string|max:255',
            'model_number' => 'nullable|string|max:255',
            'series' => 'nullable|string|max:255',
            'compatible_sizes' => 'nullable|array',
            'status' => 'boolean',
        ]);
        $model->update($data);
        return response()->json(['success' => true, 'data' => $model->load('brand')]);
    }

    public function destroy($id)
    {
        ProductModel::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Model deleted']);
    }
}
