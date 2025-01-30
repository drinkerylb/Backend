<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MediaConversion extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'media_id',
        'name',
        'conversion_name',
        'mime_type',
        'disk',
        'path',
        'size',
        'manipulation_data',
        'created_at'
    ];

    protected $casts = [
        'manipulation_data' => 'array',
        'size' => 'integer',
        'created_at' => 'datetime'
    ];

    protected $appends = [
        'url'
    ];

    public function media()
    {
        return $this->belongsTo(Media::class);
    }

    public function getUrlAttribute()
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    public function delete()
    {
        // Delete the physical file
        Storage::disk($this->disk)->delete($this->path);

        return parent::delete();
    }

    public function duplicate($newMediaId, $newPath)
    {
        $duplicate = $this->replicate();
        $duplicate->media_id = $newMediaId;
        $duplicate->path = $newPath;
        $duplicate->created_at = now();
        $duplicate->save();

        return $duplicate;
    }

    public function updateManipulationData($data)
    {
        $this->manipulation_data = array_merge($this->manipulation_data ?? [], $data);
        $this->save();
        return $this;
    }
}
