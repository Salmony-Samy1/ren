<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointsLedger extends Model
{
    use HasFactory;

    protected $table = 'points_ledger';

    protected $fillable = [
        'user_id', 'type', 'points', 'source', 'booking_id', 'expires_at', 'meta'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}

