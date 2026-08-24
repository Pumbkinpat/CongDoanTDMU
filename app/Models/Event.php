<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $table = 'Events';
    protected $fillable = ['title', 'location', 'startTime', 'endTime', 'description', 'attendeesCount', 'createdAt'];
    public $timestamps = false;

    public function articles()
    {
        return $this->hasMany(Article::class, 'eventId', 'id');
    }
}
