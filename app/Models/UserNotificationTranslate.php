<?php

namespace App\Models;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserNotificationTranslate extends Model
{
    use HasFactory;

    protected $table = 'user_notification_translates';

    protected $fillable = [
        'user_notification_id',
        'locale',
        'message',
    ];

    public function userNotification(): BelongsTo
    {
        return $this->belongsTo(UserNotification::class);
    }
}
