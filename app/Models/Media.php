<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $table = 'Media';
    protected $fillable = ['fileName', 'filePath', 'mimeType', 'fileSizeBytes', 'category', 'uploadedBy', 'uploadedAt'];
    public $timestamps = false;

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploadedBy', 'id');
    }
}
