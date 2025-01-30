<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Media;
use App\Models\MediaCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MediaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Media::query()
            ->with(['collection'])
            ->when($request->collection, function ($query, $collection) {
                $query->whereHas('collection', function ($q) use ($collection) {
                    $q->where('key', $collection);
                });
            })
            ->when($request->model_type, function ($query, $modelType) {
                $query->where('model_type', $modelType);
            })
            ->when($request->model_id, function ($query, $modelId) {
                $query->where('model_id', $modelId);
            })
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('file_name', 'like', "%{$search}%");
                });
            })
            ->orderBy($request->sort_by ?? 'created_at', $request->sort_order ?? 'desc');

        return response()->json($query->paginate($request->per_page ?? 15));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file',
            'collection' => 'nullable|string|exists:media_collections,key',
            'model_type' => 'nullable|string',
            'model_id' => 'required_with:model_type|integer',
            'custom_properties' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $file = $request->file('file');
        $collection = null;

        if ($request->collection) {
            $collection = MediaCollection::where('key', $request->collection)->first();
            if ($collection) {
                $errors = $collection->validateFile($file);
                if (!empty($errors)) {
                    throw ValidationException::withMessages(['file' => $errors]);
                }
            }
        }

        $fileName = $this->generateFileName($file);
        $path = $this->generatePath($fileName, $request->model_type, $request->model_id);
        $disk = config('filesystems.default');

        // Store the file
        $file->storeAs(dirname($path), $fileName, ['disk' => $disk]);

        // Create media record
        $media = Media::create([
            'media_collection_id' => $collection?->id,
            'model_type' => $request->model_type,
            'model_id' => $request->model_id,
            'name' => $file->getClientOriginalName(),
            'file_name' => $fileName,
            'mime_type' => $file->getMimeType(),
            'disk' => $disk,
            'path' => $path,
            'size' => $file->getSize(),
            'custom_properties' => $request->custom_properties
        ]);

        // Generate conversions if collection has conversion settings
        if ($collection && $collection->conversion_settings) {
            foreach ($collection->conversion_settings as $conversion => $settings) {
                ProcessMediaConversion::dispatch($media, $conversion, $settings);
            }
        }

        return response()->json($media, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Media $media)
    {
        return response()->json($media->load('collection', 'conversions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Media $media)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'custom_properties' => 'sometimes|array',
            'order_column' => 'sometimes|integer'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $media->update($validator->validated());

        return response()->json($media->fresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Media $media)
    {
        $media->delete();
        return response()->json(null, 204);
    }

    public function duplicate(Media $media)
    {
        $duplicate = $media->duplicate();
        return response()->json($duplicate, 201);
    }

    public function updateOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'media' => 'required|array',
            'media.*.id' => 'required|exists:media,id',
            'media.*.order' => 'required|integer'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        foreach ($request->media as $item) {
            Media::where('id', $item['id'])->update(['order_column' => $item['order']]);
        }

        return response()->json(['message' => 'Order updated successfully']);
    }

    protected function generateFileName($file): string
    {
        $extension = $file->getClientOriginalExtension();
        return uniqid() . '_' . time() . '.' . $extension;
    }

    protected function generatePath($fileName, $modelType = null, $modelId = null): string
    {
        $path = 'media';
        
        if ($modelType && $modelId) {
            $path .= '/' . strtolower(class_basename($modelType));
            $path .= '/' . $modelId;
        }
        
        return $path . '/' . $fileName;
    }
}
