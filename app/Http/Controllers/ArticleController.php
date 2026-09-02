<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Article;
use App\Models\ArticleVersion;
use App\Models\ArticleAudit;
use App\Models\Comment;
use App\Models\Category;
use App\Models\User;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $query = Article::with(['category', 'author']);

        if ($request->has('category') && $request->category !== 'all') {
            $catName = $request->category;
            $query->whereHas('category', function ($q) use ($catName) {
                $q->where('name', $catName)->orWhere('slug', $catName);
            });
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('search') && !empty($request->search)) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                  ->orWhere('summary', 'like', "%{$s}%");
            });
        }

        $articles = $query->orderBy('created_at', 'desc')->get()->map(function ($a) {
            return $this->mapArticle($a);
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

        $article->increment('views_count');

        $data = $this->mapArticle($article);
        $data['content'] = $article->content;
        $data['scheduledAt'] = $article->scheduled_at;
        $data['versions'] = $article->versions->map(function ($v) {
            return [
                'id' => $v->id,
                'versionNumber' => $v->version_number,
                'title' => $v->title,
                'content' => $v->content,
                'changeType' => $v->change_type,
                'isAiGenerated' => (bool) $v->is_ai_generated,
                'aiProvider' => $v->ai_provider,
                'aiModel' => $v->ai_model,
                'aiPrompt' => $v->ai_prompt,
                'createdAt' => $v->created_at
            ];
        });
        $data['comments'] = $article->comments->map(function ($c) {
            return [
                'id' => $c->id,
                'articleId' => $c->article_id,
                'authorName' => $c->author_name,
                'commentText' => $c->comment_text,
                'platform' => $c->platform,
                'createdAt' => $c->created_at
            ];
        });
        $data['viewsCount'] = $article->views_count;
        $data['likesCount'] = $article->likes_count;
        $data['sharesCount'] = $article->shares_count ?? 0;

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function store(Request $request)
    {
        $request->validate(['title' => 'required']);

        return DB::transaction(function () use ($request) {
            $categoryId = $this->resolveCategoryId($request->categoryName ?? null, $request->categoryId ?? null);
            $authorId = $this->resolveAuthorId($request->author ?? null, $request->authorId ?? null);
            $slug = Str::slug($request->title) . '-' . time();
            $status = $request->status ?? 'pending';
            $title = $request->title;
            $content = $request->content ?? $title;

            $article = Article::create([
                'title' => $title,
                'slug' => $slug,
                'category_id' => $categoryId,
                'author_id' => $authorId,
                'summary' => $request->summary ?? $title,
                'content' => $content,
                'featured_image' => $request->image ?? null,
                'status' => $status,
                'is_ai_generated' => $request->isAiGenerated ? 1 : 0,
                'ai_prompt' => $request->aiPrompt ?? null,
                'views_count' => 0,
                'likes_count' => 0,
                'shares_count' => 0,
            ]);

            // Save Initial Version V1
            ArticleVersion::create([
                'article_id' => $article->id,
                'version_number' => 1,
                'title' => $title,
                'content' => $content,
                'created_by' => $authorId,
                'change_type' => $request->isAiGenerated ? 'AI_GENERATED' : 'EDITOR_EDIT',
                'is_ai_generated' => $request->isAiGenerated ? 1 : 0,
                'ai_provider' => $request->isAiGenerated ? 'Google Gemini' : null,
                'ai_model' => $request->isAiGenerated ? 'gemini-2.5-flash' : null,
                'ai_prompt' => $request->aiPrompt ?? null,
            ]);

            // Audit Trail
            ArticleAudit::create([
                'article_id' => $article->id,
                'user_id' => $authorId,
                'action' => 'CREATE_ARTICLE',
                'details' => json_encode(['title' => $title, 'status' => $status]),
            ]);

            $statusMap = [
                'published' => 'Đã Xuất Bản',
                'approved' => 'Đã Duyệt',
                'pending' => 'Chờ Duyệt',
                'pending_review' => 'Chờ Duyệt',
                'draft' => 'Bản Nháp'
            ];

            return response()->json([
                'success' => true,
                'message' => 'Đã tạo bài viết và lưu Version 1 thành công (Transactional)',
                'data' => [
                    'id' => $article->id,
                    'status' => $status,
                    'statusName' => $statusMap[$status] ?? 'Đã Lưu'
                ]
            ]);
        });
    }

    public function update(Request $request, $id)
    {
        $article = Article::find($id);
        if (!$article) {
            return response()->json(['error' => 'Không tìm thấy bài viết'], 404);
        }

        return DB::transaction(function () use ($request, $article, $id) {
            $updateData = $request->only(['title', 'summary', 'content', 'status', 'scheduled_at']);
            $updateData['status'] = $request->status ?? $article->status;

            if ($request->has('categoryName') || $request->has('categoryId')) {
                $updateData['category_id'] = $this->resolveCategoryId(
                    $request->categoryName ?? null,
                    $request->categoryId ?? null
                );
            }

            if ($request->has('image')) {
                $updateData['featured_image'] = $request->image;
            }

            if ($request->has('scheduledAt')) {
                $updateData['scheduled_at'] = $request->scheduledAt;
            }

            $article->update(collect($updateData)->filter()->toArray());

            $nextVer = ArticleVersion::where('article_id', $id)->count() + 1;
            ArticleVersion::create([
                'article_id' => $id,
                'version_number' => $nextVer,
                'title' => $article->title,
                'content' => $article->content,
                'created_by' => $request->authorId ?? 1,
                'change_type' => $request->changeType ?? 'EDITOR_EDIT',
                'is_ai_generated' => $request->isAiGenerated ? 1 : 0,
                'ai_provider' => $request->aiProvider ?? null,
                'ai_model' => $request->aiModel ?? null,
                'ai_prompt' => $request->aiPrompt ?? null,
            ]);

            ArticleAudit::create([
                'article_id' => $id,
                'user_id' => $request->authorId ?? 1,
                'action' => 'UPDATE_ARTICLE',
                'details' => json_encode(['version' => $nextVer, 'title' => $article->title]),
            ]);

            $statusMap = [
                'published' => 'Đã Xuất Bản',
                'approved' => 'Đã Duyệt',
                'pending' => 'Chờ Duyệt',
                'pending_review' => 'Chờ Duyệt',
                'draft' => 'Bản Nháp'
            ];

            return response()->json([
                'success' => true,
                'message' => "Đã cập nhật bài viết và lưu phiên bản v{$nextVer} (Transactional)",
                'data' => [
                    'id' => $article->id,
                    'status' => $article->status,
                    'statusName' => $statusMap[$article->status] ?? 'Đã Lưu'
                ]
            ]);
        });
    }

    public function destroy($id)
    {
        $article = Article::find($id);
        if ($article) {
            $article->delete();
            ArticleAudit::create([
                'article_id' => null,
                'user_id' => 1,
                'action' => 'DELETE_ARTICLE',
                'details' => "Đã xóa bài viết ID #{$id}",
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa bài viết thành công khỏi CSDL (Cascade Referential Integrity)'
        ]);
    }

    public function approve($id)
    {
        $article = Article::find($id);
        if (!$article) {
            return response()->json(['error' => 'Không tìm thấy bài viết'], 404);
        }

        $article->update(['status' => 'approved']);

        ArticleAudit::create([
            'article_id' => $id,
            'user_id' => 1,
            'action' => 'APPROVE_ARTICLE',
            'details' => json_encode(['title' => $article->title]),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã duyệt bài viết thành công',
            'data' => [
                'id' => $article->id,
                'status' => 'approved',
                'statusName' => 'Đã Duyệt'
            ]
        ]);
    }

    public function like($id)
    {
        $article = Article::find($id);
        if (!$article) {
            return response()->json(['error' => 'Không tìm thấy bài viết'], 404);
        }

        $article->increment('likes_count');

        return response()->json([
            'success' => true,
            'likesCount' => $article->fresh()->likes_count
        ]);
    }

    public function addComment(Request $request, $id)
    {
        $request->validate([
            'commentText' => 'required|string'
        ]);

        $article = Article::find($id);
        if (!$article) {
            return response()->json(['error' => 'Không tìm thấy bài viết'], 404);
        }

        $comment = Comment::create([
            'article_id' => $id,
            'author_name' => $request->authorName ?? 'Ẩn Danh',
            'comment_text' => $request->commentText,
            'platform' => 'website'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã thêm bình luận thành công',
            'data' => [
                'id' => $comment->id,
                'authorName' => $comment->author_name,
                'createdAt' => $comment->created_at
            ]
        ]);
    }

    private function mapArticle(Article $a)
    {
        return [
            'id' => $a->id,
            'title' => $a->title,
            'slug' => $a->slug,
            'categoryId' => $a->category_id,
            'categoryName' => $a->category_name,
            'author' => $a->author_name,
            'authorId' => $a->author_id,
            'summary' => $a->summary,
            'content' => $a->content,
            'image' => $a->featured_image,
            'status' => $a->status,
            'statusName' => $a->status_name,
            'isAiGenerated' => (bool) $a->is_ai_generated,
            'viewsCount' => $a->views_count,
            'likesCount' => $a->likes_count,
            'sharesCount' => $a->shares_count ?? 0,
            'scheduledAt' => $a->scheduled_at,
            'createdAt' => $a->created_at
        ];
    }

    private function resolveCategoryId(?string $name, ?int $id): int
    {
        if ($id) return (int) $id;
        if ($name) {
            $cat = Category::where('name', $name)->orWhere('slug', $name)->first();
            if ($cat) return $cat->id;
        }
        return 1;
    }

    private function resolveAuthorId(?string $name, ?int $id): int
    {
        if ($id) return (int) $id;
        if ($name) {
            $user = User::where('name', $name)->first();
            if ($user) return $user->id;
        }
        return 1;
    }
}