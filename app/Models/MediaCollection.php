<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MediaCollection extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'key',
        'description',
        'allowed_mime_types',
        'allowed_file_extensions',
        'max_file_size',
        'max_files',
        'is_public',
        'is_active',
        'conversion_settings',
        'custom_properties'
    ];

    protected $casts = [
        'allowed_mime_types' => 'array',
        'allowed_file_extensions' => 'array',
        'max_file_size' => 'integer',
        'max_files' => 'integer',
        'is_public' => 'boolean',
        'is_active' => 'boolean',
        'conversion_settings' => 'array',
        'custom_properties' => 'array'
    ];

    public function media()
    {
        return $this->hasMany(Media::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function validateFile($file)
    {
        $errors = [];

        // Check file size
        if ($this->max_file_size && $file->getSize() > $this->max_file_size) {
            $errors[] = 'File size exceeds the maximum allowed size.';
        }

        // Check mime type
        if ($this->allowed_mime_types && !in_array($file->getMimeType(), $this->allowed_mime_types)) {
            $errors[] = 'File type is not allowed.';
        }

        // Check file extension
        if ($this->allowed_file_extensions) {
            $extension = strtolower($file->getClientOriginalExtension());
            if (!in_array($extension, array_map('strtolower', $this->allowed_file_extensions))) {
                $errors[] = 'File extension is not allowed.';
            }
        }

        // Check max files limit
        if ($this->max_files) {
            $currentCount = $this->media()->count();
            if ($currentCount >= $this->max_files) {
                $errors[] = 'Maximum number of files reached for this collection.';
            }
        }

        return $errors;
    }

    public function getConversionSettings($conversion)
    {
        return $this->conversion_settings[$conversion] ?? null;
    }

    public function addConversionSetting($conversion, $settings)
    {
        $conversions = $this->conversion_settings ?? [];
        $conversions[$conversion] = $settings;
        $this->conversion_settings = $conversions;
        $this->save();
        return $this;
    }

    public function removeConversionSetting($conversion)
    {
        $conversions = $this->conversion_settings ?? [];
        unset($conversions[$conversion]);
        $this->conversion_settings = $conversions;
        $this->save();
        return $this;
    }

    public function addCustomProperty($key, $value)
    {
        $properties = $this->custom_properties ?? [];
        $properties[$key] = $value;
        $this->custom_properties = $properties;
        $this->save();
        return $this;
    }

    public function removeCustomProperty($key)
    {
        $properties = $this->custom_properties ?? [];
        unset($properties[$key]);
        $this->custom_properties = $properties;
        $this->save();
        return $this;
    }

    public function clearMedia()
    {
        foreach ($this->media as $media) {
            $media->delete();
        }
        return $this;
    }
}
