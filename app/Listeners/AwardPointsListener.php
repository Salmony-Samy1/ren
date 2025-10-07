<?php

namespace App\Listeners;

use App\Events\BookingCompleted;
use App\Events\BookingCancelled;
use App\Services\PointsService;

class AwardPointsListener
{
    public function __construct(private readonly PointsService $pointsService)
    {
    }

    public function handleBookingCompleted(BookingCompleted $event): void
    {
        $this->pointsService->awardBookingPoints($event->booking);
        $this->pointsService->awardProviderBookingPoints($event->booking);
    }

    public function handleBookingCancelled(BookingCancelled $event): void
    {
        // Optionally revoke points if awarded earlier
        // $this->pointsService->revokeBookingPoints($event->booking);
    }
}

