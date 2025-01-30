<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'query',
        'filters',
        'results_count',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'filters' => 'array',
        'results_count' => 'integer'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (request()) {
                $model->ip_address = request()->ip();
                $model->user_agent = request()->userAgent();
            }
        });
    }
}
