<?php

namespace Helper;

use App\Models\News;
use Elasticsearch\ClientBuilder;

class ElasticsearchHelper
{
    public function saveNewsDataToElasticsearch($news, $index)
    {
        $host = env('ELASTICSEARCH_HOSTS');
        $elasticsearch = ClientBuilder::create()
            ->setHosts([$host])
            ->build();

        $latestData = News::orderBy('created_at', 'desc')
            ->take(count($news))
            ->get();

        foreach ($latestData as $newsModel) {
            $params['body'][] = [
                'index' => ['_index' => $index]
            ];

            $params['body'][] = [
                'id' => $newsModel->id,
                'title' => $newsModel->title,
                'description' => $newsModel->description,
                'imageUrl' => $newsModel->image_url,
                'newsUrl' => $newsModel->news_url,
                'author' => $newsModel->author,
                'source' => $newsModel->source,
                'category' => $newsModel->category,
                'publishedDate' => $newsModel->published_date,
                'created_at' => $newsModel->created_at
            ];
        }

        // if ($index != 'news') {
        //     $removeIndex = ['index' => $index];
        //     $isExists = $elasticsearch->indices()->exists($removeIndex);

        //     if ($isExists) {
        //         $elasticsearch->indices()->delete($removeIndex);
        //     }
        // } 
        $elasticsearch->bulk($params);
    }
}
