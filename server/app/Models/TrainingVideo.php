<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingVideo extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'department',
        'duration',
        'difficulty',
        'tags',
        'video_url',
        'thumbnail_url',
        'thumbnail_path',
        'video_path',
        'views',
        'likes',
        'status',
        'uploaded_by',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
