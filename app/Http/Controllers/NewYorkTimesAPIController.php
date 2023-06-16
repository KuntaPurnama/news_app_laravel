<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use App\Models\News;
use Elasticsearch\ClientBuilder;


class NewYorkTimesAPIController extends Controller
{

    public function test()
    {
        return response()->json([
            'code' => 200,
            'status' => 'OK',
            'data' => true
        ]);
    }

    public function getTopStoriesNews()
    {
        try {
            $apiKey = env('NYC_API_KEY');
            $url = 'https://api.nytimes.com/svc/topstories/v2/home.json?api-key=' . $apiKey;
            $response = Http::get($url);

            $responseData = $response->json();
            if ($responseData['status'] == 'OK' && count($responseData['results']) > 0) {
                $this->saveTopStoriesToDatabase($responseData);
            }

            return response()->json([
                'code' => 200,
                'status' => 'OK',
                'data' => true,
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

    private function saveTopStoriesToDatabase($responseData)
    {
        $listOfNews = collect($responseData['results'])->map(function ($response) {
            $newsModel = new News();
            $newsModel->title = $response['title'];
            $newsModel->description = $response['abstract'];

            if ($response['multimedia'] != null && count($response['multimedia']) > 0) {
                $media = $response['multimedia'][0];
                $newsModel->image_url = $media['url'];
            } else {
                $newsModel->image_url = null;
            }

            $newsModel->news_url = $response['url'];
            $newsModel->author = $response['byline'];
            $newsModel->source = 'New York Times';
            $newsModel->category = $response['section'];
            $newsModel->type = 'top_news';
            $newsModel->published_date = $response['published_date'];
            return $newsModel->toArray();
        })->toArray();

        News::insert($listOfNews);
        $this->saveDataToElasticSearch($listOfNews, 'top_news');
    }


    public function getMostPopular()
    {
        try {
            $apiKey = env('NYC_API_KEY');
            $url = 'https://api.nytimes.com/svc/mostpopular/v2/viewed/1.json?api-key=' . $apiKey;
            $response = Http::get($url);

            $responseData = $response->json();
            if ($responseData['status'] == 'OK' && count($responseData['results']) > 0) {
                $this->saveMostPopularToDatabase($responseData);
            }

            return response()->json([
                'code' => 200,
                'status' => 'OK',
                'data' => true,
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

    private function saveMostPopularToDatabase($responseData)
    {
        $listOfNews = collect($responseData['results'])->map(function ($response) {
            $newsModel = new News();
            $newsModel->title = $response['title'];
            $newsModel->description = $response['abstract'];

            if (count($response['media']) > 0 && count($response['media'][0]['media-metadata']) > 0) {
                $media = $response['media'][0]['media-metadata'];
                $newsModel->image_url = $media[count($media) - 1]['url'];
            } else {
                $newsModel->image_url = null;
            }

            $newsModel->news_url = $response['url'];
            $newsModel->author = $response['byline'];
            $newsModel->source = $response['source'];
            $newsModel->category = $response['section'];
            $newsModel->type = 'most_popular';
            $newsModel->published_date = $response['published_date'];
            return $newsModel->toArray();
        })->toArray();

        News::insert($listOfNews);
        $this->saveDataToElasticSearch($listOfNews, 'most_popular_news');
    }

    public function getReviewArticle()
    {
        try {
            $apiKey = env('NYC_API_KEY');
            $url = 'https://api.nytimes.com/svc/movies/v2/reviews/all.json?api-key=' . $apiKey;
            $response = Http::get($url);

            $responseData = $response->json();
            if ($responseData['status'] == 'OK' && count($responseData['results']) > 0) {
                $this->saveReviewArticleToDatabase($responseData);
            }

            return response()->json([
                'code' => 200,
                'status' => 'OK',
                'data' => true,
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

    private function saveReviewArticleToDatabase($responseData)
    {
        $listOfNews = collect($responseData['results'])->map(function ($response) {
            $newsModel = new News();
            $newsModel->title = $response['headline'];
            $newsModel->description = $response['summary_short'];
            $newsModel->image_url = $response['multimedia']['src'];
            $newsModel->news_url = $response['link']['url'];
            $newsModel->author = $response['byline'];
            $newsModel->source = 'New York Times';
            $newsModel->category = 'review';
            $newsModel->type = 'review_article';
            $newsModel->published_date = $response['publication_date'];
            return $newsModel->toArray();
        })->toArray();

        News::insert($listOfNews);
        $this->saveDataToElasticSearch($listOfNews, 'review_article_news');
    }

    private function saveDataToElasticSearch($news, $index)
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

        $elasticsearch->bulk($params);
    }
}
