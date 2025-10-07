<?php

namespace App\Jobs;

use App\Models\Property;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncPropertyToChannelsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $propertyId) {}

    public function handle(): void
    {
        $property = Property::with('service.user')->find($this->propertyId);
        if (!$property) { return; }
        // TODO: Implement channel-specific push (Airbnb, Booking.com)
        Log::info('SyncPropertyToChannelsJob dispatched', [
            'property_id' => $property->id,
            'service_id' => $property->service_id,
            'provider_id' => $property->service?->user_id,
        ]);
    }
}

