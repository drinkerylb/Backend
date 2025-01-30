<?php

namespace App\Traits;

use App\Models\Media;
use App\Models\MediaCollection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait HasMedia
{
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'model')->orderBy('order_column');
    }

    public function getMedia(string $collection = null)
    {
        return $this->media()
            ->when($collection, function ($query) use ($collection) {
                $query->whereHas('collection', function ($q) use ($collection) {
                    $q->where('key', $collection);
                });
            })
            ->get();
    }

    public function getFirstMedia(string $collection = null)
    {
        return $this->media()
            ->when($collection, function ($query) use ($collection) {
                $query->whereHas('collection', function ($q) use ($collection) {
                    $q->where('key', $collection);
                });
            })
            ->orderBy('order_column')
            ->first();
    }

    public function addMedia(UploadedFile $file, string $collection = null, array $properties = [])
    {
        $mediaCollection = null;
        if ($collection) {
            $mediaCollection = MediaCollection::where('key', $collection)->first();
            if ($mediaCollection) {
                $errors = $mediaCollection->validateFile($file);
                if (!empty($errors)) {
                    throw new \Exception(implode(' ', $errors));
                }
            }
        }

        $fileName = $this->generateFileName($file);
        $path = $this->generatePath($fileName);
        $disk = config('filesystems.default');

        // Store the file
        $file->storeAs(dirname($path), $fileName, ['disk' => $disk]);

        // Create media record
        $media = new Media([
            'media_collection_id' => $mediaCollection?->id,
            'name' => $file->getClientOriginalName(),
            'file_name' => $fileName,
            'mime_type' => $file->getMimeType(),
            'disk' => $disk,
            'path' => $path,
            'size' => $file->getSize(),
            'custom_properties' => $properties,
        ]);

        $this->media()->save($media);

        // Generate conversions if collection has conversion settings
        if ($mediaCollection && $mediaCollection->conversion_settings) {
            $this->generateConversions($media, $mediaCollection);
        }

        return $media;
    }

    public function addMediaFromUrl(string $url, string $collection = null, array $properties = [])
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'media_');
        file_put_contents($tempFile, file_get_contents($url));

        $file = new UploadedFile(
            $tempFile,
            basename($url),
            mime_content_type($tempFile),
            null,
            true
        );

        $media = $this->addMedia($file, $collection, array_merge($properties, [
            'original_url' => $url
        ]));

        unlink($tempFile);

        return $media;
    }

    public function clearMediaCollection(string $collection = null)
    {
        $this->media()
            ->when($collection, function ($query) use ($collection) {
                $query->whereHas('collection', function ($q) use ($collection) {
                    $q->where('key', $collection);
                });
            })
            ->get()
            ->each
            ->delete();

        return $this;
    }

    protected function generateFileName(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        return Str::random(40) . '.' . $extension;
    }

    protected function generatePath(string $fileName): string
    {
        $folderName = Str::plural(Str::snake(class_basename($this)));
        return $folderName . '/' . $this->id . '/' . $fileName;
    }

    protected function generateConversions(Media $media, MediaCollection $collection)
    {
        // This method should be implemented based on your image manipulation requirements
        // You might want to use a job queue for this
        foreach ($collection->conversion_settings as $conversion => $settings) {
            // Example implementation:
            // ProcessMediaConversion::dispatch($media, $conversion, $settings);
        }
    }

    public function syncMedia(array $mediaIds, string $collection = null)
    {
        $query = $this->media();
        
        if ($collection) {
            $query->whereHas('collection', function ($q) use ($collection) {
                $q->where('key', $collection);
            });
        }

        $existingIds = $query->pluck('id')->toArray();
        $deletedIds = array_diff($existingIds, $mediaIds);
        $newIds = array_diff($mediaIds, $existingIds);

        // Delete removed media
        if (!empty($deletedIds)) {
            Media::whereIn('id', $deletedIds)->get()->each->delete();
        }

        // Attach new media
        if (!empty($newIds)) {
            Media::whereIn('id', $newIds)->update([
                'model_type' => get_class($this),
                'model_id' => $this->id
            ]);
        }

        // Update order
        foreach ($mediaIds as $order => $id) {
            Media::where('id', $id)->update(['order_column' => $order]);
        }

        return $this;
    }

    public function getMediaUrl(string $collection = null, string $conversion = null): ?string
    {
        $media = $this->getFirstMedia($collection);
        return $media ? $media->getUrl($conversion) : null;
    }

    public function hasMedia(string $collection = null): bool
    {
        return $this->media()
            ->when($collection, function ($query) use ($collection) {
                $query->whereHas('collection', function ($q) use ($collection) {
                    $q->where('key', $collection);
                });
            })
            ->exists();
    }
} 