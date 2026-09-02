<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $table = 'articles';

    protected $fillable = [
        'title',
        'slug',
        'category_id',
        'author_id',
        'event_id',
        'summary',
        'content',
        'featured_image',
        'status',
        'is_ai_generated',
        'ai_prompt',
        'views_count',
        'likes_count',
        'shares_count',
        'published_at',
        'scheduled_at'
    ];

    // Normalization Accessors (Zero Redundancy in DB)
    public function getCategoryNameAttribute()
    {
        return $this->category ? $this->category->name : 'Thông Báo';
    }

    public function getAuthorNameAttribute()
    {
        return $this->author ? $this->author->name : 'Ban Thường Vụ TDMU';
    }

    public function getStatusNameAttribute()
    {
        $map = [
            'draft' => 'Bản Nháp',
            'pending' => 'Chờ Duyệt',
            'pending_review' => 'Chờ Duyệt',
            'approved' => 'Đã Duyệt',
            'published' => 'Đã Xuất Bản',
            'scheduled' => 'Đã Lên Lịch',
            'rejected' => 'Từ Chối',
            'archived' => 'Lưu Trữ',
            'hidden' => 'Ẩn'
        ];
        return $map[$this->status] ?? 'Đã Xuất Bản';
    }

    // Relationships
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id', 'id');
    }

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id', 'id');
    }

    public function versions()
    {
        return $this->hasMany(ArticleVersion::class, 'article_id', 'id')->orderBy('version_number', 'desc');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'article_id', 'id')->orderBy('created_at', 'desc');
    }
}