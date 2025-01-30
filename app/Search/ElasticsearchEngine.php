<?php

namespace App\Search;

use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;
use Elasticsearch\Client;
use Illuminate\Database\Eloquent\Collection;

class ElasticsearchEngine extends Engine
{
    protected $elasticsearch;
    protected $softDelete;

    public function __construct(Client $elasticsearch, $softDelete = false)
    {
        $this->elasticsearch = $elasticsearch;
        $this->softDelete = $softDelete;
    }

    public function update($models)
    {
        if ($models->isEmpty()) {
            return;
        }

        $params['body'] = [];

        $models->each(function ($model) use (&$params) {
            $params['body'][] = [
                'index' => [
                    '_index' => $model->searchableAs(),
                    '_id' => $model->getKey(),
                ]
            ];

            $params['body'][] = array_merge(
                $model->toSearchableArray(),
                $model->scoutMetadata()
            );
        });

        $this->elasticsearch->bulk($params);
    }

    public function delete($models)
    {
        $params['body'] = [];

        $models->each(function ($model) use (&$params) {
            $params['body'][] = [
                'delete' => [
                    '_index' => $model->searchableAs(),
                    '_id' => $model->getKey(),
                ]
            ];
        });

        $this->elasticsearch->bulk($params);
    }

    public function search(Builder $builder)
    {
        return $this->performSearch($builder, array_filter([
            'numericFilters' => $this->filters($builder),
            'size' => $builder->limit,
        ]));
    }

    public function paginate(Builder $builder, $perPage, $page)
    {
        $result = $this->performSearch($builder, [
            'numericFilters' => $this->filters($builder),
            'from' => ($page - 1) * $perPage,
            'size' => $perPage,
        ]);

        $result['nbPages'] = ceil($result['hits']['total']['value'] / $perPage);

        return $result;
    }

    protected function performSearch(Builder $builder, array $options = [])
    {
        $query = [
            'bool' => [
                'must' => [
                    ['query_string' => [
                        'query' => "*{$builder->query}*",
                        'fields' => ['name^5', 'description'],
                        'analyze_wildcard' => true,
                    ]],
                ],
                'filter' => [],
            ],
        ];

        // Handle faceted search
        if ($facets = $builder->model->facets ?? null) {
            foreach ($facets as $facet) {
                $query['bool']['filter'][] = [
                    'nested' => [
                        'path' => $facet['field'],
                        'query' => [
                            'term' => [
                                "{$facet['field']}.{$facet['key']}" => $facet['value']
                            ]
                        ]
                    ]
                ];
            }
        }

        // Handle price range
        if ($priceRange = $builder->model->priceRange ?? null) {
            $query['bool']['filter'][] = [
                'range' => [
                    'price' => [
                        'gte' => $priceRange['min'],
                        'lte' => $priceRange['max']
                    ]
                ]
            ];
        }

        $params = [
            'index' => $builder->model->searchableAs(),
            'body' => [
                'query' => $query,
                'sort' => [['_score' => 'desc']],
                'aggs' => [
                    'categories' => [
                        'nested' => ['path' => 'categories'],
                        'aggs' => [
                            'names' => ['terms' => ['field' => 'categories.name']]
                        ]
                    ],
                    'tags' => [
                        'nested' => ['path' => 'tags'],
                        'aggs' => [
                            'names' => ['terms' => ['field' => 'tags.name']]
                        ]
                    ],
                    'price_ranges' => [
                        'range' => [
                            'field' => 'price',
                            'ranges' => [
                                ['to' => 50],
                                ['from' => 50, 'to' => 100],
                                ['from' => 100, 'to' => 200],
                                ['from' => 200]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        if (isset($options['from'])) {
            $params['body']['from'] = $options['from'];
        }

        if (isset($options['size'])) {
            $params['body']['size'] = $options['size'];
        }

        if ($this->softDelete && $builder->model->usesSoftDelete()) {
            $query['bool']['must'][] = ['term' => ['__soft_deleted' => 0]];
        }

        return $this->elasticsearch->search($params);
    }

    public function mapIds($results)
    {
        return collect($results['hits']['hits'])->pluck('_id')->values();
    }

    public function map(Builder $builder, $results, $model)
    {
        if ($results['hits']['total']['value'] === 0) {
            return Collection::make();
        }

        $ids = collect($results['hits']['hits'])->pluck('_id')->values()->all();

        $models = $model->getScoutModelsByIds(
            $builder, $ids
        )->keyBy(function ($model) {
            return $model->getScoutKey();
        });

        return Collection::make($results['hits']['hits'])->map(function ($hit) use ($models) {
            return isset($models[$hit['_id']]) ? $models[$hit['_id']] : null;
        })->filter()->values();
    }

    public function getTotalCount($results)
    {
        return $results['hits']['total']['value'];
    }

    protected function filters(Builder $builder)
    {
        return collect($builder->wheres)->map(function ($value, $key) {
            return ['match' => [$key => $value]];
        })->values()->all();
    }

    public function flush($model)
    {
        $this->elasticsearch->indices()->delete([
            'index' => $model->searchableAs()
        ]);
    }

    public function suggest(Builder $builder)
    {
        $params = [
            'index' => $builder->model->searchableAs(),
            'body' => [
                'suggest' => [
                    'product_suggest' => [
                        'prefix' => $builder->query,
                        'completion' => [
                            'field' => 'name.suggest',
                            'size' => 5,
                            'fuzzy' => [
                                'fuzziness' => 'AUTO'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return $this->elasticsearch->search($params);
    }
}
