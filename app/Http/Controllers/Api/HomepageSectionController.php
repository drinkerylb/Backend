<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HomepageSection;
use App\Models\HomepageSectionItem;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HomepageSectionController extends Controller
{
    public function index()
    {
        $sections = HomepageSection::with(['items' => function($query) {
                $query->orderBy('position');
            }, 'items.itemable'])
            ->ordered()
            ->get();

        return response()->json(['data' => $sections]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'type' => ['required', 'string', Rule::in(['products', 'categories', 'custom'])],
            'is_active' => 'boolean',
            'position' => 'integer',
            'settings' => 'nullable|array',
        ]);

        $section = HomepageSection::create($validated);

        return response()->json(['data' => $section], 201);
    }

    public function show(HomepageSection $section)
    {
        return response()->json([
            'data' => $section->load(['items' => function($query) {
                $query->orderBy('position');
            }, 'items.itemable'])
        ]);
    }

    public function update(Request $request, HomepageSection $section)
    {
        $validated = $request->validate([
            'title' => 'string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'type' => ['string', Rule::in(['products', 'categories', 'custom'])],
            'is_active' => 'boolean',
            'position' => 'integer',
            'settings' => 'nullable|array',
        ]);

        $section->update($validated);

        return response()->json(['data' => $section]);
    }

    public function destroy(HomepageSection $section)
    {
        $section->delete();
        return response()->json(null, 204);
    }

    public function addItem(Request $request, HomepageSection $section)
    {
        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(['product', 'category', 'custom'])],
            'item_id' => 'required_unless:type,custom|integer',
            'position' => 'integer',
            'custom_fields' => 'nullable|array',
        ]);

        $item = new HomepageSectionItem([
            'position' => $validated['position'] ?? 0,
            'custom_fields' => $validated['custom_fields'] ?? null,
        ]);

        if ($validated['type'] !== 'custom') {
            $modelClass = $validated['type'] === 'product' ? Product::class : Category::class;
            $itemable = $modelClass::findOrFail($validated['item_id']);
            $item->itemable()->associate($itemable);
        }

        $section->items()->save($item);

        return response()->json(['data' => $item->load('itemable')], 201);
    }

    public function removeItem(HomepageSection $section, HomepageSectionItem $item)
    {
        if ($item->homepage_section_id !== $section->id) {
            return response()->json(['message' => 'Item does not belong to this section'], 403);
        }

        $item->delete();
        return response()->json(null, 204);
    }

    public function reorderItems(Request $request, HomepageSection $section)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*' => 'integer|exists:homepage_section_items,id'
        ]);

        foreach ($validated['items'] as $position => $itemId) {
            HomepageSectionItem::where('id', $itemId)
                ->where('homepage_section_id', $section->id)
                ->update(['position' => $position]);
        }

        return response()->json(['message' => 'Items reordered successfully']);
    }

    public function reorderSections(Request $request)
    {
        $validated = $request->validate([
            'sections' => 'required|array',
            'sections.*' => 'integer|exists:homepage_sections,id'
        ]);

        foreach ($validated['sections'] as $position => $sectionId) {
            HomepageSection::where('id', $sectionId)
                ->update(['position' => $position]);
        }

        return response()->json(['message' => 'Sections reordered successfully']);
    }
} 