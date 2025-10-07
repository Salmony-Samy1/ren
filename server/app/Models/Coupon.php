<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code','type','amount','min_total','max_uses','per_user_limit','status','start_at','end_at','meta'
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'meta' => 'array',
    ];

    public function redemptions()
    {
        return $this->hasMany(CouponRedemption::class);
    }
}

