<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $table = 'Comments';
    protected $fillable = ['articleId', 'userId', 'authorName', 'commentText', 'platform', 'createdAt'];
    public $timestamps = false;

    public function article()
    {
        return $this->belongsTo(Article::class, 'articleId', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'userId', 'id');
    }
}
