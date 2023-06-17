<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use App\Models\News;
use Helper\ElasticsearchHelper;

class NewsAPIController extends Controller
{
    private $elasticsearchHelper;

    public function __construct()
    {
        $this->elasticsearchHelper = new ElasticsearchHelper();
    }

    public function getTopHeadlinesNewsAPI($category)
    {
        try {
            $apiKey = env('NEWS_API_KEY');
            $url = 'https://newsapi.org/v2/top-headlines?country=us&category=' . $category . '&apiKey=' . $apiKey . '&page=1&pageSize=5';
            $response = Http::get($url);
            $responseData = $response->json();

            if ($responseData['status'] == 'ok' && count($responseData['articles']) > 0) {
                $news = collect($responseData['articles'])->map(function ($response) use ($category) {
                    $newsModel = new News();
                    $newsModel->title = $response['title'];
                    $newsModel->description = $response['description'];
                    $newsModel->image_url = $response['urlToImage'];
                    $newsModel->news_url = $response['url'];
                    $newsModel->author = $response['author'];
                    $newsModel->source = $response['source']['name'];
                    $newsModel->category = ucwords($category);
                    $newsModel->type = 'top_news';
                    $newsModel->published_date = $response['publishedAt'];
                    return $newsModel->toArray();
                })->toArray();

                News::insert($news);
                $this->elasticsearchHelper->saveNewsDataToElasticsearch($news, 'top_news');
                $this->elasticsearchHelper->saveNewsDataToElasticsearch($news, 'news');
            }

            return response()->json([
                'code' => 200,
                'status' => 'OK',
                'data' => true,
            ]);
        } catch (\Exception $e) {
            info("API ERROR Top News News API", ["messge", $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
