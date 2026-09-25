<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    public function index()
    {
        $brands = Brand::withCount('products')->orderBy('name')->get();
        return response()->json(['success' => true, 'data' => $brands]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'logo' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'boolean',
        ]);
        $data['slug'] = Str::slug($data['name']);

        $brand = Brand::create($data);
        return response()->json(['success' => true, 'data' => $brand], 201);
    }

    public function update(Request $request, $id)
    {
        $brand = Brand::findOrFail($id);
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'logo' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'boolean',
        ]);
        $brand->update($data);
        return response()->json(['success' => true, 'data' => $brand]);
    }

    public function destroy($id)
    {
        Brand::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Brand deleted']);
    }
}
