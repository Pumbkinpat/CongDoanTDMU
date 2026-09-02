<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Audit extends Model
{
    protected $table = 'article_audits';

    protected $fillable = ['article_id', 'user_id', 'action', 'details', 'changes_summary'];

    protected $appends = ['userName', 'createdAt'];

    public function getCreatedAtAttribute($value)
    {
        return $value;
    }

    public function getUserNameAttribute()
    {
        return $this->user ? $this->user->name : 'Hệ Thống';
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function article()
    {
        return $this->belongsTo(Article::class, 'article_id', 'id');
    }
}