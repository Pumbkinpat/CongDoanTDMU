<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $table = 'media';

    protected $fillable = [
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'category',
        'uploaded_by'
    ];

    protected $appends = ['filePath', 'fileSize', 'uploadedAt'];

    public function getFilePathAttribute()
    {
        return $this->attributes['file_path'] ?? '';
    }

    public function getFileSizeAttribute()
    {
        $bytes = $this->attributes['file_size'] ?? 0;
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }

    public function getUploadedAtAttribute()
    {
        return $this->created_at;
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'id');
    }
}