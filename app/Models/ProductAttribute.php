<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductAttribute extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type', // text, number, boolean, select, etc.
        'description',
        'is_required',
        'is_filterable',
        'is_visible',
        'validation_rules',
        'default_value',
        'options', // JSON array for select type
        'sort_order'
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_filterable' => 'boolean',
        'is_visible' => 'boolean',
        'options' => 'array',
        'validation_rules' => 'array'
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_attribute_values')
            ->withPivot('value')
            ->withTimestamps();
    }
}
