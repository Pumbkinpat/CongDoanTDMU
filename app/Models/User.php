<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'Users';

    protected $fillable = ['name', 'email', 'passwordHash', 'roleId', 'department', 'createdAt'];
    protected $hidden = ['passwordHash'];
    public $timestamps = false;

    public function role()
    {
        return $this->belongsTo(Role::class, 'roleId', 'id');
    }

    public function articles()
    {
        return $this->hasMany(Article::class, 'authorId', 'id');
    }

    public function versions()
    {
        return $this->hasMany(ArticleVersion::class, 'createdBy', 'id');
    }
}
