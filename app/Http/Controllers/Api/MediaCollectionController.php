<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MediaCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MediaCollectionController extends Controller
{
    public function index(Request $request)
    {
        $query = MediaCollection::query()
            ->when($request->active, function ($query) {
                $query->where('is_active', true);
            })
            ->when($request->public, function ($query) {
                $query->where('is_public', true);
            })
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('key', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy($request->sort_by ?? 'name', $request->sort_order ?? 'asc');

        return response()->json($query->paginate($request->per_page ?? 15));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'key' => 'required|string|max:255|unique:media_collections,key',
            'description' => 'nullable|string',
            'allowed_mime_types' => 'nullable|array',
            'allowed_mime_types.*' => 'string',
            'allowed_file_extensions' => 'nullable|array',
            'allowed_file_extensions.*' => 'string',
            'max_file_size' => 'nullable|integer',
            'max_files' => 'nullable|integer',
            'is_public' => 'boolean',
            'is_active' => 'boolean',
            'conversion_settings' => 'nullable|array',
            'custom_properties' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $collection = MediaCollection::create($validator->validated());

        return response()->json($collection, 201);
    }

    public function show(MediaCollection $mediaCollection)
    {
        return response()->json($mediaCollection);
    }

    public function update(Request $request, MediaCollection $mediaCollection)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'key' => 'sometimes|string|max:255|unique:media_collections,key,' . $mediaCollection->id,
            'description' => 'nullable|string',
            'allowed_mime_types' => 'nullable|array',
            'allowed_mime_types.*' => 'string',
            'allowed_file_extensions' => 'nullable|array',
            'allowed_file_extensions.*' => 'string',
            'max_file_size' => 'nullable|integer',
            'max_files' => 'nullable|integer',
            'is_public' => 'boolean',
            'is_active' => 'boolean',
            'conversion_settings' => 'nullable|array',
            'custom_properties' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $mediaCollection->update($validator->validated());

        return response()->json($mediaCollection->fresh());
    }

    public function destroy(MediaCollection $mediaCollection)
    {
        // Clear all media in the collection
        $mediaCollection->clearMedia();
        
        // Delete the collection
        $mediaCollection->delete();

        return response()->json(null, 204);
    }
} 