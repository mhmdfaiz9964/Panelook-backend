<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('products')->orderBy('sort_order')->get();
        return response()->json(['success' => true, 'data' => $categories]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'parent_id' => 'nullable|integer|exists:categories,id',
            'status' => 'boolean',
            'sort_order' => 'integer',
        ]);
        $data['slug'] = Str::slug($data['name']) . '-' . Str::random(4);

        $category = Category::create($data);
        return response()->json(['success' => true, 'data' => $category], 201);
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'parent_id' => 'nullable|integer|exists:categories,id',
            'status' => 'boolean',
            'sort_order' => 'integer',
        ]);
        $category->update($data);
        return response()->json(['success' => true, 'data' => $category]);
    }

    public function destroy($id)
    {
        Category::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Category deleted']);
    }
}
