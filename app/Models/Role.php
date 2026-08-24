<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'Roles';
    protected $fillable = ['name', 'displayName', 'description', 'createdAt'];
    public $timestamps = false;

    public function users()
    {
        return $this->hasMany(User::class, 'roleId', 'id');
    }
}
