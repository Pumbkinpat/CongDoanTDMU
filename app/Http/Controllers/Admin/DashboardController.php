<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Event;
use App\Models\User;

class DashboardController extends Controller {
    public function index() {
        $totalArticles = Article::count();
        $totalViews = Article::sum('views_count');
        $aiArticles = Article::where('is_ai_generated', true)->count();
        $publishedArticles = Article::where('status', 'published')->count();

        $topArticles = Article::with('category')->orderBy('views_count', 'desc')->take(5)->get();

        return view('admin.dashboard.index', compact('totalArticles', 'totalViews', 'aiArticles', 'publishedArticles', 'topArticles'));
    }
}
