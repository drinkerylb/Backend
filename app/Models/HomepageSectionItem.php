<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomepageSectionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'homepage_section_id',
        'position',
        'custom_fields',
    ];

    protected $casts = [
        'custom_fields' => 'array',
    ];

    public function section()
    {
        return $this->belongsTo(HomepageSection::class, 'homepage_section_id');
    }

    public function itemable()
    {
        return $this->morphTo(__FUNCTION__, 'itemable_type', 'itemable_id')->withDefault();
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }
} 