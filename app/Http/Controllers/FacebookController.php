<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Article;

class FacebookController extends Controller
{
    public function publish(Request $request)
    {
        $articleId = $request->input('articleId');
        $title = $request->input('title', '');

        $art = Article::find($articleId);
        if ($art) {
            $art->update(['status' => 'published']);
        }

        return response()->json([
            'success' => true,
            'facebookPostId' => 'simulated_fb_' . ($articleId ?? time()),
            'message' => "[MÔ PHỎNG XUẤT BẢN FANPAGE FACEBOOK OK] Đã chuyển bài viết \"{$title}\" sang trạng thái xuất bản Fanpage TDMU!"
        ]);
    }
}