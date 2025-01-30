<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cart extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'session_id',
        'status', // active, abandoned, converted
        'metadata',
        'expires_at'
    ];

    protected $casts = [
        'metadata' => 'array',
        'expires_at' => 'datetime'
    ];

    protected $with = ['items'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function getSubtotalAttribute()
    {
        return $this->items->sum(function ($item) {
            return $item->quantity * $item->unit_price;
        });
    }

    public function getTotalAttribute()
    {
        $subtotal = $this->subtotal;
        // Add tax, shipping, etc. calculations here
        return $subtotal;
    }

    public function getItemCountAttribute()
    {
        return $this->items->sum('quantity');
    }

    public function addItem($product, $quantity = 1, $variant = null)
    {
        $existingItem = $this->items()
            ->where('product_id', $product->id)
            ->where('variant_id', $variant?->id)
            ->first();

        if ($existingItem) {
            $existingItem->increment('quantity', $quantity);
            return $existingItem;
        }

        return $this->items()->create([
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'quantity' => $quantity,
            'unit_price' => $variant?->price ?? $product->price
        ]);
    }

    public function updateItemQuantity($itemId, $quantity)
    {
        if ($quantity <= 0) {
            return $this->removeItem($itemId);
        }

        return $this->items()->findOrFail($itemId)->update(['quantity' => $quantity]);
    }

    public function removeItem($itemId)
    {
        return $this->items()->findOrFail($itemId)->delete();
    }

    public function clear()
    {
        return $this->items()->delete();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeAbandoned($query)
    {
        return $query->where('status', 'abandoned');
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }
} 