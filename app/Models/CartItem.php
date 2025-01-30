<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CartItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'cart_id',
        'product_id',
        'variant_id',
        'quantity',
        'unit_price',
        'metadata'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'metadata' => 'array'
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function getSubtotalAttribute()
    {
        return $this->quantity * $this->unit_price;
    }

    protected static function booted()
    {
        static::creating(function ($item) {
            if (!$item->unit_price) {
                $item->unit_price = $item->variant?->price ?? $item->product->price;
            }
        });
    }
} 