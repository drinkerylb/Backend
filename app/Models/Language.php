<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Language extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'locale',
        'flag',
        'is_rtl',
        'is_active',
        'is_default',
        'date_format',
        'number_format',
        'currency_format',
        'sort_order'
    ];

    protected $casts = [
        'is_rtl' => 'boolean',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'date_format' => 'array',
        'number_format' => 'array',
        'currency_format' => 'array'
    ];

    public function translations()
    {
        return $this->hasMany(Translation::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public static function getDefault()
    {
        return static::where('is_default', true)->first();
    }

    public function formatDate($date, $format = null)
    {
        $format = $format ?? $this->date_format['default'] ?? 'Y-m-d H:i:s';
        return $date instanceof \DateTime ? $date->format($format) : date($format, strtotime($date));
    }

    public function formatNumber($number, $decimals = 2)
    {
        $format = $this->number_format;
        return number_format(
            $number,
            $decimals,
            $format['decimal_point'] ?? '.',
            $format['thousands_separator'] ?? ','
        );
    }

    public function formatCurrency($amount, $currency = null)
    {
        $format = $this->currency_format;
        $symbol = $currency ?? ($format['symbol'] ?? '$');
        $formatted = $this->formatNumber($amount, $format['decimals'] ?? 2);
        
        return $format['position'] === 'before'
            ? $symbol . $formatted
            : $formatted . $symbol;
    }
}
