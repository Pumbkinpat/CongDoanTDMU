<?php

namespace App\Http\Controllers;

use App\Models\Comment;

class InboxController extends Controller
{
    public function index()
    {
        $comments = Comment::with('article')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'articleId' => $c->article_id,
                    'articleTitle' => $c->article ? $c->article->title : 'Không rõ',
                    'authorName' => $c->author_name,
                    'commentText' => $c->comment_text,
                    'platform' => $c->platform,
                    'createdAt' => $c->created_at
                ];
            });

        return response()->json(['success' => true, 'data' => $comments]);
    }
}