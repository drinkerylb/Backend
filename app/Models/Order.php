<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'order_number',
        'subtotal',
        'tax',
        'shipping',
        'total',
        'coupon_id',
        'discount',
        'status',
        'shipping_address',
        'billing_address',
        'payment_method',
        'payment_status'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'shipping' => 'decimal:2',
        'total' => 'decimal:2',
        'discount' => 'decimal:2'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'order_items')
            ->withPivot(['quantity', 'unit_price', 'subtotal'])
            ->withTimestamps();
    }

    public function recalculateTotal()
    {
        $this->subtotal = $this->items->sum('subtotal');
        $this->tax = $this->subtotal * 0.15; // 15% tax rate
        $this->total = $this->subtotal + $this->tax + $this->shipping - $this->discount;
        $this->save();
    }
}
