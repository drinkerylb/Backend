<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Laravel\Scout\EngineManager;
use App\Search\ElasticsearchEngine;

class ElasticsearchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(Client::class, function ($app) {
            return ClientBuilder::create()
                ->setHosts([config('elasticsearch.hosts')])
                ->build();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        resolve(EngineManager::class)->extend('elasticsearch', function () {
            return new ElasticsearchEngine(
                resolve(Client::class),
                config('scout.soft_delete', false)
            );
        });
    }
}
