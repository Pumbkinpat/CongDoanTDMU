<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'users';

    protected $fillable = ['name', 'email', 'password', 'role_id', 'department'];
    protected $hidden = ['password', 'remember_token'];
    protected $appends = ['roleName'];

    public function getRoleNameAttribute()
    {
        return $this->role ? $this->role->display_name : 'Cộng Tác Viên';
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function articles()
    {
        return $this->hasMany(Article::class, 'author_id', 'id');
    }

    public function versions()
    {
        return $this->hasMany(ArticleVersion::class, 'created_by', 'id');
    }
}