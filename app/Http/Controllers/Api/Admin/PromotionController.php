<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function index()
    {
        return response()->json(['success' => true, 'data' => Promotion::orderBy('id', 'desc')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:flash_sale,weekend_sale,brand_sale,category_sale,clearance',
            'description' => 'nullable|string',
            'discount_value' => 'nullable|numeric',
            'discount_type' => 'in:percentage,fixed',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'banner_image' => 'nullable|string',
            'status' => 'boolean',
        ]);
        return response()->json(['success' => true, 'data' => Promotion::create($data)], 201);
    }

    public function update(Request $request, $id)
    {
        $promotion = Promotion::findOrFail($id);
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:flash_sale,weekend_sale,brand_sale,category_sale,clearance',
            'description' => 'nullable|string',
            'discount_value' => 'nullable|numeric',
            'discount_type' => 'in:percentage,fixed',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'banner_image' => 'nullable|string',
            'status' => 'boolean',
        ]);
        $promotion->update($data);
        return response()->json(['success' => true, 'data' => $promotion]);
    }

    public function destroy($id)
    {
        Promotion::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Promotion deleted']);
    }
}
