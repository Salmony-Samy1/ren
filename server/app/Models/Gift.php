<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gift extends Model
{
    protected $fillable = [
        'sender_id', 'recipient_id', 'type', 'amount', 'gift_package_id', 'service_id', 'message', 'status', 'expires_at', 'accepted_at', 'rejected_at'
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(GiftPackage::class, 'gift_package_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }
}

