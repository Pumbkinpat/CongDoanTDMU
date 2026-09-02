<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleVersion extends Model
{
    protected $table = 'article_versions';

    protected $fillable = [
        'article_id',
        'version_number',
        'title',
        'content',
        'created_by',
        'change_type',
        'is_ai_generated',
        'ai_provider',
        'ai_model',
        'ai_prompt'
    ];

    public function article()
    {
        return $this->belongsTo(Article::class, 'article_id', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}