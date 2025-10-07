<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\SoftDeletes;

use Spatie\MediaLibrary\InteractsWithMedia;

class Experience extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'user_id',
        'main_service_id',
        'caption',
        'is_public',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function mainService()
    {
        return $this->belongsTo(MainService::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('experience_images');
    }
}