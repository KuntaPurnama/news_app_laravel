<?php

namespace App\Http\Controllers;

use Elasticsearch\ClientBuilder;

class NewsController extends Controller
{
    public function getNewsSummary($index, $size)
    {
        $host = env('ELASTICSEARCH_HOSTS');
        $elasticsearch = ClientBuilder::create()
            ->setHosts([$host])
            ->build();

        $params = [
            'index' => $index,
            'body' => [
                'query' => [
                    'match_all' => new \stdClass(),
                ],
                'size' => $size,
            ],
        ];

        $news = $elasticsearch->search($params);
        $newsData = $news['hits']['hits'];
        $response = count($newsData) > 0 ? collect($newsData)->pluck('_source')->all() : [];

        return response()->json([
            'code' => 200,
            'status' => 'OK',
            'data' => $response
        ]);
    }
}
