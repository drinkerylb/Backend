<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class BannerController extends Controller
{
    public function index()
    {
        $banners = Banner::active()
            ->orderBy('position')
            ->get();

        return response()->json(['data' => $banners]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'required|image|max:2048',
            'link_url' => 'nullable|url',
            'is_active' => 'boolean',
            'position' => 'integer',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('banners', 'public');
            $validated['image_path'] = $path;
        }

        $banner = Banner::create($validated);

        return response()->json(['data' => $banner], 201);
    }

    public function show(Banner $banner)
    {
        return response()->json(['data' => $banner]);
    }

    public function update(Request $request, Banner $banner)
    {
        $validated = $request->validate([
            'title' => 'string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'link_url' => 'nullable|url',
            'is_active' => 'boolean',
            'position' => 'integer',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
        ]);

        if ($request->hasFile('image')) {
            // Delete old image
            if ($banner->image_path) {
                Storage::disk('public')->delete($banner->image_path);
            }
            
            $path = $request->file('image')->store('banners', 'public');
            $validated['image_path'] = $path;
        }

        $banner->update($validated);

        return response()->json(['data' => $banner]);
    }

    public function destroy(Banner $banner)
    {
        if ($banner->image_path) {
            Storage::disk('public')->delete($banner->image_path);
        }
        
        $banner->delete();

        return response()->json(null, 204);
    }
} 