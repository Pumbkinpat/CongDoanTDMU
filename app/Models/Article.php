<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Article extends Model
{
    use HasFactory;

    protected $table = 'Articles';

    protected $fillable = [
        'title',
        'slug',
        'categoryId',
        'authorId',
        'eventId',
        'summary',
        'content',
        'image',
        'status',
        'isAiGenerated',
        'viewsCount',
        'likesCount',
        'sharesCount',
        'scheduledAt',
        'createdAt',
        'updatedAt'
    ];

    public $timestamps = false;

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
            'pending_review' => 'Chờ Duyệt',
            'approved' => 'Đã Duyệt',
            'published' => 'Đã Xuất Bản',
            'scheduled' => 'Đã Lên Lịch',
            'rejected' => 'Từ Chối',
            'archived' => 'Lưu Trữ'
        ];
        return $map[$this->status] ?? 'Đã Xuất Bản';
    }

    // Relationships
    public function category()
    {
        return $this->belongsTo(Category::class, 'categoryId', 'id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'authorId', 'id');
    }

    public function event()
    {
        return $this->belongsTo(Event::class, 'eventId', 'id');
    }

    public function versions()
    {
        return $this->hasMany(ArticleVersion::class, 'articleId', 'id')->orderBy('versionNumber', 'desc');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'articleId', 'id')->orderBy('createdAt', 'desc');
    }
}
