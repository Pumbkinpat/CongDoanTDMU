<?php

namespace App\Http\Controllers;

use App\Models\Article;

class AnalyticsController extends Controller
{
    public function index()
    {
        $totalArticles = Article::count();
        $totalViews = Article::sum('views_count');
        $totalLikes = Article::sum('likes_count');
        $totalShares = Article::sum('shares_count');
        $aiArticlesCount = Article::where('is_ai_generated', true)->count();
        $publishedCount = Article::where('status', 'published')->count();

        return response()->json([
            'success' => true,
            'totalArticles' => $totalArticles,
            'totalViews' => $totalViews,
            'totalLikes' => $totalLikes,
            'totalShares' => $totalShares,
            'aiArticlesCount' => $aiArticlesCount,
            'publishedCount' => $publishedCount
        ]);
    }
}