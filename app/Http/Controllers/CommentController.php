<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Comment;

class CommentController extends Controller
{
    public function destroy($id)
    {
        $comment = Comment::find($id);
        if ($comment) {
            $comment->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa bình luận thành công!'
        ]);
    }
}