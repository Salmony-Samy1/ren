<?php

namespace App\Events;

use App\Models\Gift;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GiftRejected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Gift $gift)
    {
    }
}

