<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'type',
        'value',
        'valid_from',
        'valid_until',
        'max_uses',
        'times_used',
        'min_order_value',
        'is_active'
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'value' => 'decimal:2',
        'min_order_value' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function isValid()
    {
        return $this->is_active &&
            $this->valid_from <= now() &&
            ($this->valid_until === null || $this->valid_until >= now()) &&
            ($this->max_uses === null || $this->times_used < $this->max_uses);
    }

    public function calculateDiscount($orderTotal)
    {
        if (!$this->isValid() || ($this->min_order_value && $orderTotal < $this->min_order_value)) {
            return 0;
        }

        return $this->type === 'percentage'
            ? ($orderTotal * ($this->value / 100))
            : min($this->value, $orderTotal);
    }
}
