<?php

namespace App\Http\Controllers;

use Elasticsearch\ClientBuilder;

class NewsController extends Controller
{
    private $elasticsearch;

    public function  __construct()
    {
        $host = env('ELASTICSEARCH_HOSTS');
        $this->elasticsearch = ClientBuilder::create()
            ->setHosts([$host])
            ->build();
    }

    public function getNewsSummary($index, $size)
    {
        $twoDaysAgo = \Carbon\Carbon::now()->subDays(2)->format('Y-m-d');
        $params = [
            'index' => $index,
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'exists' => [
                                    'field' => 'imageUrl'
                                ]
                            ],
                            [
                                'range' => [
                                    'publishedDate' => [
                                        'gte' => $twoDaysAgo,
                                    ],
                                ],
                            ]
                        ]
                    ],
                ],
                'size' => $size,
                'sort' => [
                    'created_at' => 'desc',
                ],
            ],
        ];

        $news = $this->elasticsearch->search($params);
        $response = $this->extractData($news);

        return response()->json([
            'code' => 200,
            'status' => 'OK',
            'data' => $response
        ]);
    }

    public function getThisWeekNews($size)
    {
        $startOfWeek = \Carbon\Carbon::now()->startOfWeek()->format('Y-m-d');
        $params = [
            'index' => 'news',
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'exists' => [
                                    'field' => 'imageUrl'
                                ]
                            ],
                            [
                                'range' => [
                                    'publishedDate' => [
                                        'gte' => $startOfWeek,
                                    ],
                                ],
                            ]
                        ]
                    ],
                ],
                'size' => $size,
                'sort' => [
                    'created_at' => 'desc',
                ],
            ],
        ];

        $news = $this->elasticsearch->search($params);
        $response = $this->extractData($news);

        return response()->json([
            'code' => 200,
            'status' => 'OK',
            'data' => $response
        ]);
    }

    public function getMoreNews($size)
    {
        $params = [
            'index' => 'news',
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'exists' => [
                                    'field' => 'imageUrl'
                                ]
                            ]
                        ]
                    ],
                ],
                'size' => $size,
                'sort' => [
                    'created_at' => 'asc',
                ],
            ],
        ];

        $news = $this->elasticsearch->search($params);
        $response = $this->extractData($news);

        return response()->json([
            'code' => 200,
            'status' => 'OK',
            'data' => $response
        ]);
    }

    public function getAllTopics()
    {
        $params = [
            'index' => 'news',
            'body' => [
                'aggs' => [
                    'unique_categories' => [
                        'terms' => [
                            'field' => 'category.keyword',
                            'size' => 1000,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->elasticsearch->search($params);
        $response = array_column($response['aggregations']['unique_categories']['buckets'], 'key');

        return response()->json([
            'code' => 200,
            'status' => 'OK',
            'data' => $response
        ]);
    }

    private function extractData($news)
    {
        $newsData = $news['hits']['hits'];
        $response = count($newsData) > 0 ? collect($newsData)->pluck('_source')->all() : [];

        return $response;
    }
}
