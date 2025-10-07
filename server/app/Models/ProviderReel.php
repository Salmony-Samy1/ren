<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ProviderReel extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $guarded = [];

    protected $casts = [
        'is_public' => 'boolean',
        'main_service_id' => 'integer',
        'views' => 'integer',
        'likes' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('reel_videos');
        $this->addMediaCollection('reel_thumbnails')->singleFile();
    }

    public function getVideoUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('reel_videos');
        return $media ? $media->getUrl() : null;
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        $thumbnail = $this->getFirstMediaUrl('reel_thumbnails');
        return $thumbnail ?: $this->getFirstMediaUrl('reel_videos');
    }
}

