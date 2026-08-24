<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleAudit extends Model {
    protected $fillable = ['article_id', 'user_id', 'action', 'changes_summary'];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
