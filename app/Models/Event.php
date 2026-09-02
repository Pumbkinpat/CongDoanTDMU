<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $table = 'events';

    protected $fillable = [
        'title',
        'location',
        'start_time',
        'end_time',
        'description',
        'banner_image',
        'status',
        'attendees_count'
    ];

    protected $appends = ['startTime', 'attendeesCount'];

    public function getStartTimeAttribute($value)
    {
        return $value;
    }

    public function getAttendeesCountAttribute()
    {
        return $this->attributes['attendees_count'] ?? 0;
    }

    public function articles()
    {
        return $this->hasMany(Article::class, 'event_id', 'id');
    }
}