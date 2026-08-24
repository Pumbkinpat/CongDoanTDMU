<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Audit extends Model
{
    protected $table = 'Audits';
    protected $fillable = ['articleId', 'userId', 'action', 'details', 'createdAt'];
    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class, 'userId', 'id');
    }

    public function article()
    {
        return $this->belongsTo(Article::class, 'articleId', 'id');
    }
}
