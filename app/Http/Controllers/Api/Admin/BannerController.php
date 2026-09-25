<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function index()
    {
        return response()->json(['success' => true, 'data' => Banner::orderBy('sort_order')->get()]);
    }

    public function publicBanners()
    {
        $banners = Banner::where('status', true)->orderBy('sort_order')->get();
        return response()->json(['success' => true, 'data' => $banners]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'desktop_image' => 'nullable|string',
            'mobile_image' => 'nullable|string',
            'button_text' => 'nullable|string|max:100',
            'button_url' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'sort_order' => 'integer',
            'status' => 'boolean',
        ]);
        return response()->json(['success' => true, 'data' => Banner::create($data)], 201);
    }

    public function update(Request $request, $id)
    {
        $banner = Banner::findOrFail($id);
        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'desktop_image' => 'nullable|string',
            'mobile_image' => 'nullable|string',
            'button_text' => 'nullable|string|max:100',
            'button_url' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'sort_order' => 'integer',
            'status' => 'boolean',
        ]);
        $banner->update($data);
        return response()->json(['success' => true, 'data' => $banner]);
    }

    public function destroy($id)
    {
        Banner::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Banner deleted']);
    }
}
