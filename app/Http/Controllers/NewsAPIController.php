<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\News;

class NewsAPIController extends Controller
{
    public function getTopHeadlinesNewsAPI()
    {
        try {
            $apiKey = env('NEWS_API_KEY');
            $url = 'https://newsapi.org/v2/top-headlines?country=us&apiKey=' . $apiKey;
            $response = Http::get($url);
            $responseData = $response->json();

            // if ($responseData['status'] == 'ok' && count($responseData['articles']) > 0) {
            //     $news = collect($responseData['articles']->map(function ($response) {
            //         $newsModel = new News();
            //         $newsModel->title = $response['title'];
            //         $newsModel->description = $response['description'];
            //         $newsModel->imageUrl = $response['urlToImage'];
            //         $newsModel->newsUrl = $response['url'];
            //         $newsModel->author = $response['author'];
            //         $newsModel->source = $response['source']['name'];
            //         $newsModel->category = $response[''];
            //         $newsModel->publishedDate = $response['title'];
            //     }));
            // }

            // $news = new News();

            return $responseData;
        } catch (\Exception $e) {
            info("API ERROR", ["messge", $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
