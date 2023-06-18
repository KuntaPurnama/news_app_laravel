<?php

namespace App\Http\Controllers;

use Elasticsearch\ClientBuilder;
use Illuminate\Http\Request;


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

    public function getAllAuthors()
    {
        $params = [
            'index' => 'news',
            'body' => [
                'aggs' => [
                    'unique_authors' => [
                        'terms' => [
                            'field' => 'author.keyword',
                            'size' => 1000,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->elasticsearch->search($params);
        $response = array_column($response['aggregations']['unique_authors']['buckets'], 'key');

        return response()->json([
            'code' => 200,
            'status' => 'OK',
            'data' => $response
        ]);
    }

    public function getAllSources()
    {
        $params = [
            'index' => 'news',
            'body' => [
                'aggs' => [
                    'unique_sources' => [
                        'terms' => [
                            'field' => 'source.keyword',
                            'size' => 1000,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->elasticsearch->search($params);
        $response = array_column($response['aggregations']['unique_sources']['buckets'], 'key');

        return response()->json([
            'code' => 200,
            'status' => 'OK',
            'data' => $response
        ]);
    }

    public function getNewsAndRelatedData(Request $request)
    {
        try {
            //Past
            $startOfThisYear = \Carbon\Carbon::now()->startOfYear();
            $startOfThisMonth = \Carbon\Carbon::now()->startOfMonth();

            //Current
            $thisWeek = \Carbon\Carbon::now()->startOfWeek();
            $category = $request->input('category');
            $index = $request->input('index');
            $author = $request->input('author');
            $source = $request->input('source');
            $publishedDate = $request->input('publishedDate');

            $params = [
                'index' => $index,
                'body' => [
                    'size' => 100,
                    'query' => [
                        'bool' => [
                            'must' => [],
                            'filter' => [],
                        ],
                    ],
                    'aggs' => [
                        'published_this_week' => [
                            'filter' => [
                                'range' => [
                                    'publishedDate' => [
                                        'gte' => $thisWeek,
                                    ],
                                ],
                            ],
                        ],
                        'published_this_month' => [
                            'filter' => [
                                'range' => [
                                    'publishedDate' => [
                                        'gte' => $startOfThisMonth,
                                    ],
                                ],
                            ],
                        ],
                        'published_this_year' => [
                            'filter' => [
                                'range' => [
                                    'publishedDate' => [
                                        'gte' => $startOfThisYear,
                                    ],
                                ],
                            ],
                        ],
                        'sources' => [
                            'terms' => [
                                'field' => 'source.keyword',
                                'size' => 100,
                            ],
                        ],
                        'authors' => [
                            'terms' => [
                                'field' => 'author.keyword',
                                'size' => 100,
                            ],
                        ],
                    ],
                ],
            ];

            if ($category !== null) {
                $params['body']['query']['bool']['filter'][] = [
                    'term' => ['category.keyword' => $category],
                ];
            }

            // Add the author filter if it is not null
            if ($author !== null) {
                $params['body']['query']['bool']['filter'][] = [
                    'term' => ['author.keyword' => $author],
                ];
            }

            if ($source !== null) {
                $params['body']['query']['bool']['filter'][] = [
                    'term' => ['source.keyword' => $source],
                ];
            }

            // Add the publishedDate filter if it is not null
            if ($publishedDate !== null) {
                $params['body']['query']['bool']['filter'][] = [
                    'range' => [
                        'publishedDate' => [
                            'gte' => $publishedDate,
                        ],
                    ],
                ];
            } else {
                $params['body']['query']['bool']['filter'][] = [
                    'range' => [
                        'publishedDate' => [
                            'gte' => $startOfThisYear,
                        ],
                    ],
                ];
            }


            $response = $this->elasticsearch->search($params);
            $totalDocuments = $response['hits']['total']['value'];

            $publishedThisWeek = $response['aggregations']['published_this_week']['doc_count'];
            $publishedThisMonth = $response['aggregations']['published_this_month']['doc_count'];
            $publishedThisYear = $response['aggregations']['published_this_year']['doc_count'];
            $authors = $response['aggregations']['authors']['buckets'];
            $sources = $response['aggregations']['sources']['buckets'];

            // Create the JSON response array
            $jsonResponse = [
                'total_documents' => $totalDocuments,
                'published_this_week' => $publishedThisWeek,
                'published_this_month' => $publishedThisMonth,
                'published_this_year' => $publishedThisYear,
                'sources' => $sources,
                'authors' => $authors,
                'documents' => $this->extractData($response),
            ];


            // Return the JSON response using Laravel's response() helper
            return response()->json([
                'code' => 200,
                'status' => 'OK',
                'data' => $jsonResponse
            ]);
        } catch (\Exception $e) {
            info("API ERROR", ["messge", $e->getMessage()]);
            return response()->json([
                'code' => 500,
                'status' => 'INTERNAL_SERVER_ERROR',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function searchNews(Request $request)
    {
        try {
            $keywords = $request->input('keyword');
            $query = [
                'query' => [
                    'bool' => [
                        'should' => [
                            [
                                'match_phrase' => [
                                    'title' => [
                                        'query' => $keywords,
                                        'slop' => 2, // Allow two words deviation between terms
                                    ],
                                ],
                            ],
                            [
                                'match_phrase' => [
                                    'author' => [
                                        'query' => $keywords,
                                        'slop' => 2,
                                    ],
                                ],
                            ],
                            [
                                'match_phrase' => [
                                    'source' => [
                                        'query' => $keywords,
                                        'slop' => 2,
                                    ],
                                ],
                            ],
                            [
                                'match_phrase' => [
                                    'description' => [
                                        'query' => $keywords,
                                        'slop' => 2,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'sort' => [
                    '_score',
                ],
                'size' => 10
            ];
            $response = $this->elasticsearch->search([
                'index' => 'news',
                'body' => $query,
            ]);

            // Get the search results
            $news = $this->extractData($response);

            return response()->json([
                'code' => 200,
                'status' => 'OK',
                'data' => $news
            ]);
        } catch (\Exception $e) {
            info("API ERROR", ["messge", $e->getMessage()]);
            return response()->json([
                'code' => 500,
                'status' => 'INTERNAL_SERVER_ERROR',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function extractData($news)
    {
        $newsData = $news['hits']['hits'];
        $response = count($newsData) > 0 ? collect($newsData)->pluck('_source')->all() : [];

        return $response;
    }
}
