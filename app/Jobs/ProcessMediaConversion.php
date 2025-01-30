<?php

namespace App\Jobs;

use App\Models\Media;
use App\Models\MediaConversion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class ProcessMediaConversion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected Media $media,
        protected string $conversionName,
        protected array $settings
    ) {}

    public function handle()
    {
        // Skip if conversion already exists
        if ($this->media->hasGeneratedConversion($this->conversionName)) {
            return;
        }

        // Get the original image
        $originalPath = Storage::disk($this->media->disk)->path($this->media->path);
        $image = Image::make($originalPath);

        // Apply manipulations
        $this->applyManipulations($image);

        // Generate new filename and path
        $extension = pathinfo($this->media->file_name, PATHINFO_EXTENSION);
        $newFileName = pathinfo($this->media->file_name, PATHINFO_FILENAME) 
            . '-' . $this->conversionName 
            . '.' . $extension;
        $newPath = str_replace($this->media->file_name, $newFileName, $this->media->path);

        // Save the converted image
        $disk = config('filesystems.default');
        Storage::disk($disk)->put(
            $newPath,
            $image->stream()->__toString()
        );

        // Create conversion record
        MediaConversion::create([
            'media_id' => $this->media->id,
            'name' => $this->conversionName,
            'conversion_name' => $this->conversionName,
            'mime_type' => $image->mime(),
            'disk' => $disk,
            'path' => $newPath,
            'size' => Storage::disk($disk)->size($newPath),
            'manipulation_data' => $this->settings,
            'created_at' => now()
        ]);

        // Mark conversion as complete
        $this->media->markConversionAsComplete($this->conversionName);
    }

    protected function applyManipulations($image)
    {
        foreach ($this->settings as $manipulation => $params) {
            switch ($manipulation) {
                case 'resize':
                    $this->handleResize($image, $params);
                    break;
                case 'crop':
                    $this->handleCrop($image, $params);
                    break;
                case 'rotate':
                    $image->rotate($params);
                    break;
                case 'flip':
                    $image->flip($params);
                    break;
                case 'brightness':
                    $image->brightness($params);
                    break;
                case 'contrast':
                    $image->contrast($params);
                    break;
                case 'greyscale':
                    if ($params) {
                        $image->greyscale();
                    }
                    break;
                case 'blur':
                    $image->blur($params);
                    break;
                case 'optimize':
                    if ($params) {
                        $image->optimize();
                    }
                    break;
                case 'quality':
                    $image->quality($params);
                    break;
            }
        }
    }

    protected function handleResize($image, $params)
    {
        $width = $params['width'] ?? null;
        $height = $params['height'] ?? null;
        $maintainAspectRatio = $params['maintain_aspect_ratio'] ?? true;
        $preventUpsize = $params['prevent_upsize'] ?? true;

        if ($maintainAspectRatio) {
            $image->resize($width, $height, function ($constraint) use ($preventUpsize) {
                $constraint->aspectRatio();
                if ($preventUpsize) {
                    $constraint->upsize();
                }
            });
        } else {
            $image->resize($width, $height, function ($constraint) use ($preventUpsize) {
                if ($preventUpsize) {
                    $constraint->upsize();
                }
            });
        }
    }

    protected function handleCrop($image, $params)
    {
        $width = $params['width'] ?? null;
        $height = $params['height'] ?? null;
        $x = $params['x'] ?? null;
        $y = $params['y'] ?? null;

        if (isset($params['method']) && $params['method'] === 'fit') {
            $image->fit($width, $height, function ($constraint) {
                $constraint->upsize();
            });
        } else {
            $image->crop($width, $height, $x, $y);
        }
    }
} 