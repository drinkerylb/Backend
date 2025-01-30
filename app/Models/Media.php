<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Media extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'media_collection_id',
        'name',
        'file_name',
        'mime_type',
        'disk',
        'path',
        'size',
        'custom_properties',
        'responsive_images',
        'generated_conversions',
        'original_url',
        'order_column'
    ];

    protected $casts = [
        'custom_properties' => 'array',
        'responsive_images' => 'array',
        'generated_conversions' => 'array',
        'size' => 'integer',
        'order_column' => 'integer'
    ];

    protected $appends = [
        'url',
        'thumbnail_url'
    ];

    public function collection()
    {
        return $this->belongsTo(MediaCollection::class, 'media_collection_id');
    }

    public function conversions()
    {
        return $this->hasMany(MediaConversion::class);
    }

    public function model()
    {
        return $this->morphTo();
    }

    public function getUrlAttribute()
    {
        return $this->getUrl();
    }

    public function getThumbnailUrlAttribute()
    {
        return $this->getUrl('thumbnail');
    }

    public function getUrl($conversion = '')
    {
        if ($conversion && isset($this->generated_conversions[$conversion])) {
            $conversion = $this->conversions()->where('conversion_name', $conversion)->first();
            if ($conversion) {
                return Storage::disk($conversion->disk)->url($conversion->path);
            }
        }

        return Storage::disk($this->disk)->url($this->path);
    }

    public function delete()
    {
        // Delete the original file
        Storage::disk($this->disk)->delete($this->path);

        // Delete all conversions
        foreach ($this->conversions as $conversion) {
            Storage::disk($conversion->disk)->delete($conversion->path);
            $conversion->delete();
        }

        return parent::delete();
    }

    public function duplicate()
    {
        $duplicate = $this->replicate(['order_column']);
        $duplicate->file_name = $this->generateDuplicateFileName();
        $duplicate->path = $this->generateDuplicatePath();
        
        // Copy the original file
        Storage::disk($this->disk)->copy($this->path, $duplicate->path);
        
        $duplicate->save();

        // Duplicate conversions
        foreach ($this->conversions as $conversion) {
            $newConversion = $conversion->replicate();
            $newConversion->media_id = $duplicate->id;
            $newConversion->path = str_replace($this->file_name, $duplicate->file_name, $conversion->path);
            
            Storage::disk($conversion->disk)->copy($conversion->path, $newConversion->path);
            
            $newConversion->save();
        }

        return $duplicate;
    }

    protected function generateDuplicateFileName()
    {
        $extension = pathinfo($this->file_name, PATHINFO_EXTENSION);
        $name = pathinfo($this->file_name, PATHINFO_FILENAME);
        return $name . '-copy-' . Str::random(8) . '.' . $extension;
    }

    protected function generateDuplicatePath()
    {
        return str_replace($this->file_name, $this->generateDuplicateFileName(), $this->path);
    }

    public function setOrder($order)
    {
        $this->order_column = $order;
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

    public function hasGeneratedConversion($conversion)
    {
        return isset($this->generated_conversions[$conversion]) && $this->generated_conversions[$conversion];
    }

    public function markConversionAsComplete($conversion)
    {
        $conversions = $this->generated_conversions ?? [];
        $conversions[$conversion] = true;
        $this->generated_conversions = $conversions;
        $this->save();
        return $this;
    }
}
