<?php

return [
    'hosts' => [
        env('ELASTICSEARCH_HOST', 'localhost:9200')
    ],
    'indices' => [
        'mappings' => [
            'default' => [
                'properties' => [
                    'id' => ['type' => 'keyword'],
                    'name' => [
                        'type' => 'text',
                        'analyzer' => 'english',
                        'fields' => [
                            'raw' => ['type' => 'keyword'],
                            'suggest' => ['type' => 'completion']
                        ]
                    ],
                    'description' => [
                        'type' => 'text',
                        'analyzer' => 'english'
                    ],
                    'price' => ['type' => 'float'],
                    'categories' => [
                        'type' => 'nested',
                        'properties' => [
                            'id' => ['type' => 'keyword'],
                            'name' => ['type' => 'keyword']
                        ]
                    ],
                    'tags' => [
                        'type' => 'nested',
                        'properties' => [
                            'id' => ['type' => 'keyword'],
                            'name' => ['type' => 'keyword']
                        ]
                    ],
                    'attributes' => [
                        'type' => 'nested',
                        'properties' => [
                            'name' => ['type' => 'keyword'],
                            'value' => ['type' => 'keyword']
                        ]
                    ],
                    'created_at' => ['type' => 'date'],
                    'updated_at' => ['type' => 'date'],
                ]
            ]
        ],
        'settings' => [
            'default' => [
                'number_of_shards' => 1,
                'number_of_replicas' => 0,
                'analysis' => [
                    'analyzer' => [
                        'ngram_analyzer' => [
                            'type' => 'custom',
                            'tokenizer' => 'ngram_tokenizer',
                            'filter' => ['lowercase']
                        ]
                    ],
                    'tokenizer' => [
                        'ngram_tokenizer' => [
                            'type' => 'ngram',
                            'min_gram' => 2,
                            'max_gram' => 3,
                            'token_chars' => ['letter', 'digit']
                        ]
                    ]
                ]
            ]
        ]
    ]
]; 