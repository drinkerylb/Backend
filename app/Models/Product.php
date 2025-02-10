<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Product extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
        'sku',
        'image_url',
        'is_active'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_items')
            ->withPivot(['quantity', 'unit_price', 'subtotal'])
            ->withTimestamps();
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class)
            ->withTimestamps();
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class)
            ->withTimestamps();
    }

    public function productAttributes()
    {
        return $this->belongsToMany(ProductAttribute::class, 'product_attribute_values')
            ->withPivot('value')
            ->withTimestamps();
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function homepageSectionItems()
    {
        return $this->morphMany(HomepageSectionItem::class, 'itemable');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->whereHas('categories', function ($q) use ($category) {
            $q->where('slug', $category);
        });
    }

    public function scopeByTag($query, $tag)
    {
        return $query->whereHas('tags', function ($q) use ($tag) {
            $q->where('slug', $tag);
        });
    }

    public function searchableAs()
    {
        return 'products_index';
    }

    public function toSearchableArray()
    {
        $array = $this->toArray();

        // Include categories if they exist
        if ($this->relationLoaded('categories')) {
            $array['categories'] = $this->categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name
                ];
            })->toArray();
        }

        // Include tags if they exist
        if ($this->relationLoaded('tags')) {
            $array['tags'] = $this->tags->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name
                ];
            })->toArray();
        }

        // Include product attributes if they exist
        if ($this->relationLoaded('productAttributes')) {
            $array['product_attributes'] = $this->productAttributes->map(function ($attribute) {
                return [
                    'name' => $attribute->name,
                    'value' => $attribute->pivot->value
                ];
            })->toArray();
        }

        // Remove any unnecessary fields that shouldn't be searchable
        unset(
            $array['created_at'],
            $array['updated_at'],
            $array['deleted_at']
        );

        return $array;
    }

    public function shouldBeSearchable()
    {
        return $this->is_active;
    }
}
