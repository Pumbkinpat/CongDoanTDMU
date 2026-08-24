<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Article;
use App\Models\ArticleVersion;
use App\Models\Audit;
use App\Models\Category;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $query = Article::with(['category', 'author']);

        if ($request->has('category') && $request->category !== 'all') {
            $catName = $request->category;
            $query->whereHas('category', function($q) use ($catName) {
                $q->where('name', $catName)->orWhere('slug', $catName);
            });
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('search') && !empty($request->search)) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                  ->orWhere('summary', 'like', "%{$s}%");
            });
        }

        $articles = $query->orderBy('createdAt', 'desc')->get()->map(function($a) {
            return [
                'id' => $a->id,
                'title' => $a->title,
                'slug' => $a->slug,
                'categoryId' => $a->categoryId,
                'categoryName' => $a->category_name,
                'author' => $a->author_name,
                'authorId' => $a->authorId,
                'summary' => $a->summary,
                'content' => $a->content,
                'image' => $a->image,
                'status' => $a->status,
                'statusName' => $a->status_name,
                'isAiGenerated' => (bool)$a->isAiGenerated,
                'viewsCount' => $a->viewsCount,
                'likesCount' => $a->likesCount,
                'sharesCount' => $a->sharesCount,
                'createdAt' => $a->createdAt
            ];
        });

        return response()->json([
            'success' => true,
            'count' => $articles->count(),
            'data' => $articles
        ]);
    }

    public function show($id)
    {
        $article = Article::with(['category', 'author', 'versions', 'comments'])->find($id);
        if (!$article) {
            return response()->json(['error' => 'Không tìm thấy bài viết'], 404);
        }

        $article->increment('viewsCount');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'categoryId' => $article->categoryId,
                'categoryName' => $article->category_name,
                'author' => $article->author_name,
                'authorId' => $article->authorId,
                'summary' => $article->summary,
                'content' => $article->content,
                'image' => $article->image,
                'status' => $article->status,
                'statusName' => $article->status_name,
                'isAiGenerated' => (bool)$article->isAiGenerated,
                'viewsCount' => $article->viewsCount,
                'likesCount' => $article->likesCount,
                'sharesCount' => $article->sharesCount,
                'versions' => $article->versions,
                'comments' => $article->comments,
                'createdAt' => $article->createdAt
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate(['title' => 'required']);

        return DB::transaction(function() use ($request) {
            $catId = $request->categoryId ?? 1;
            $title = $request->title;
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title)) . '-' . time();
            $status = $request->status ?? 'pending_review';

            $article = Article::create([
                'title' => $title,
                'slug' => $slug,
                'categoryId' => $catId,
                'authorId' => $request->authorId ?? 1,
                'summary' => $request->summary ?? $title,
                'content' => $request->content ?? $title,
                'image' => $request->image ?? 'images/banner.jpg',
                'status' => $status,
                'isAiGenerated' => $request->isAiGenerated ? 1 : 0,
                'viewsCount' => 0,
                'likesCount' => 0,
                'sharesCount' => 0,
                'createdAt' => date('Y-m-d H:i:s'),
                'updatedAt' => date('Y-m-d H:i:s')
            ]);

            // Save Initial Version V1
            ArticleVersion::create([
                'articleId' => $article->id,
                'versionNumber' => 1,
                'title' => $title,
                'content' => $request->content ?? $title,
                'createdBy' => $request->authorId ?? 1,
                'changeType' => $request->isAiGenerated ? 'AI_GENERATED' : 'EDITOR_EDIT',
                'isAiGenerated' => $request->isAiGenerated ? 1 : 0,
                'aiProvider' => $request->isAiGenerated ? 'Google Gemini' : null,
                'aiModel' => $request->isAiGenerated ? 'gemini-2.5-flash' : null,
                'aiPrompt' => $request->aiPrompt ?? null,
                'createdAt' => date('Y-m-d H:i:s')
            ]);

            // Audit Trail
            Audit::create([
                'articleId' => $article->id,
                'userId' => $request->authorId ?? 1,
                'action' => 'CREATE_ARTICLE',
                'details' => json_encode(['title' => $title, 'status' => $status]),
                'createdAt' => date('Y-m-d H:i:s')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đã tạo bài viết và lưu Version 1 thành công (Transactional)',
                'data' => $article
            ]);
        });
    }

    public function update(Request $request, $id)
    {
        $article = Article::find($id);
        if (!$article) {
            return response()->json(['error' => 'Không tìm thấy bài viết'], 404);
        }

        return DB::transaction(function() use ($request, $article, $id) {
            $article->update($request->only([
                'title', 'categoryId', 'summary', 'content', 'image', 'status', 'scheduledAt'
            ]));
            $article->updatedAt = date('Y-m-d H:i:s');
            $article->save();

            $nextVer = ArticleVersion::where('articleId', $id)->count() + 1;
            ArticleVersion::create([
                'articleId' => $id,
                'versionNumber' => $nextVer,
                'title' => $article->title,
                'content' => $article->content,
                'createdBy' => $request->authorId ?? 1,
                'changeType' => $request->changeType ?? 'EDITOR_EDIT',
                'isAiGenerated' => $request->isAiGenerated ? 1 : 0,
                'aiProvider' => $request->aiProvider ?? null,
                'aiModel' => $request->aiModel ?? null,
                'aiPrompt' => $request->aiPrompt ?? null,
                'createdAt' => date('Y-m-d H:i:s')
            ]);

            Audit::create([
                'articleId' => $id,
                'userId' => $request->authorId ?? 1,
                'action' => 'UPDATE_ARTICLE',
                'details' => json_encode(['version' => $nextVer, 'title' => $article->title]),
                'createdAt' => date('Y-m-d H:i:s')
            ]);

            return response()->json([
                'success' => true,
                'message' => "Đã cập nhật bài viết và lưu phiên bản v{$nextVer} (Transactional)",
                'data' => $article
            ]);
        });
    }

    public function destroy($id)
    {
        $article = Article::find($id);
        if ($article) {
            $article->delete(); // Cascades ArticleVersions & Comments automatically in SQL Server!
            Audit::create([
                'articleId' => null,
                'userId' => 1,
                'action' => 'DELETE_ARTICLE',
                'details' => "Đã xóa bài viết ID #{$id}",
                'createdAt' => date('Y-m-d H:i:s')
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa bài viết thành công khỏi CSDL (Cascade Referential Integrity)'
        ]);
    }
}
