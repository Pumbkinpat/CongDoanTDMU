<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'Categories';
    protected $fillable = ['name', 'slug', 'description', 'createdAt'];
    public $timestamps = false;

    public function articles()
    {
        return $this->hasMany(Article::class, 'categoryId', 'id');
    }
}
