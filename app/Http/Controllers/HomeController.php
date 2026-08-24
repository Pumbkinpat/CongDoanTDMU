<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\Event;
use App\Models\Comment;
use Illuminate\Http\Request;

class HomeController extends Controller {
    public function index() {
        $articles = Article::with('category', 'author')
            ->where('status', 'published')
            ->orderBy('created_at', 'desc')
            ->get();

        $categories = Category::all();
        $events = Event::where('status', 'upcoming')->orderBy('start_time', 'asc')->take(3)->get();

        return view('home.index', compact('articles', 'categories', 'events'));
    }

    public function show($slug) {
        $article = Article::with('category', 'author', 'comments')->where('slug', $slug)->firstOrFail();
        $article->increment('views_count');

        return view('home.detail', compact('article'));
    }

    public function comment(Request $request, $id) {
        $request->validate([
            'author_name' => 'required|string|max:100',
            'comment_text' => 'required|string'
        ]);

        Comment::create([
            'article_id' => $id,
            'author_name' => $request->author_name,
            'comment_text' => $request->comment_text
        ]);

        return back()->with('success', 'Đã gửi bình luận thành công!');
    }
}
