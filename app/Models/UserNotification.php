<?php

namespace App\Models;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserNotification extends Model
{
    use HasFactory, Translatable;

    protected $table = 'user_notifications';

    protected $fillable = [
        'user_id',
        'action',
        'is_read',
    ];

    public $translatedAttributes = ['message'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function translations(): HasMany
    {
        return $this->hasMany(UserNotificationTranslate::class);
    }
}