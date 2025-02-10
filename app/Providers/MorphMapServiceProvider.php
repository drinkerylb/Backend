<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use App\Models\Product;
use App\Models\Category;

class MorphMapServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Relation::morphMap([
            'product' => Product::class,
            'category' => Category::class,
        ]);
    }
} 