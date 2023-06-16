<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    use HasFactory;

    protected $table = 'news';
    public $timestamps = true;
    protected $fillable = ['id', 'title', 'description', 'image_url', 'news_url', 'author', 'source', 'category', 'type', 'published_date', 'created_at', 'updated_at'];
}
