<?php

namespace App\Http\Controllers;

use App\Models\News;
use Helper\ElasticsearchHelper;
use Illuminate\Support\Facades\Http;

class NewsDataAPIController extends Controller
{
    private $elasticsearchHelper;

    public function __construct()
    {
        $this->elasticsearchHelper = new ElasticsearchHelper();
    }

    public function getMostRecentNews()
    {
        try {
            $apiKey = env('NEWS_DATA_API_KEY');
            $url = 'https://newsdata.io/api/1/news?apikey=' . $apiKey . '&language=en';
            $response = Http::get($url);

            $responseData = $response->json();
            if ($responseData['status'] == 'success' && count($responseData['results']) > 0) {
                $listOfNews = collect($responseData['results'])->map(function ($response) {
                    $newsModel = new News();
                    $newsModel->title = $response['title'];
                    $newsModel->description = $response['description'];
                    $newsModel->image_url = $response['image_url'];
                    $newsModel->news_url = $response['link'];
                    if ($response['creator'] != null && count($response['creator']) > 0) {
                        $newsModel->author = strtolower($response['creator'][0]);
                    } else {
                        $newsModel->author = null;
                    }

                    $newsModel->source = $response['source_id'];

                    if ($response['keywords'] != null && count($response['keywords']) > 0) {
                        $newsModel->category = ucwords($response['keywords'][0]);
                    } else {
                        $newsModel->category = null;
                    }

                    $newsModel->type = 'recent_news';
                    $newsModel->published_date = $response['pubDate'];

                    info("isi", [$newsModel]);
                    return $newsModel->toArray();
                })->toArray();

                News::insert($listOfNews);
                $this->elasticsearchHelper->saveNewsDataToElasticsearch($listOfNews, 'recent_news');
                $this->elasticsearchHelper->saveNewsDataToElasticsearch($listOfNews, 'news');
            }

            return response()->json([
                'code' => 200,
                'status' => 'OK',
                'data' => true,
            ]);
        } catch (\Exception $e) {
            info("API ERROR Most Recent News Data", ["messge", $e->getMessage()]);
            return response()->json([
                'code' => 500,
                'status' => 'INTERNAL_SERVER_ERROR',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
