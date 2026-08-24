<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleVersion extends Model
{
    protected $table = 'ArticleVersions';

    protected $fillable = [
        'articleId',
        'versionNumber',
        'title',
        'content',
        'createdBy',
        'changeType',
        'isAiGenerated',
        'aiProvider',
        'aiModel',
        'aiPrompt',
        'createdAt'
    ];

    public $timestamps = false;

    public function article()
    {
        return $this->belongsTo(Article::class, 'articleId', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'createdBy', 'id');
    }
}
