<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function neigbourhood(): BelongsTo
    {
        return $this->belongsTo(Neigbourhood::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ActivityImage::class, 'activity_id');
    }

    protected function casts(): array
    {
        return [
            'date' => 'timestamp',
        ];
    }

    public function wishedBy(): MorphMany
    {
        return $this->morphMany(User::class, 'wishable', 'wishable_type', 'wishable_id');;
    }
}
