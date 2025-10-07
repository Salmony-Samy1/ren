<?php

namespace App\Providers;

use App\Events\BookingCancelled;
use App\Events\BookingCompleted;
use App\Listeners\AwardPointsListener;
use App\Listeners\SendBookingNotificationsListener;
use App\Listeners\GiftNotificationsListener;
use App\Events\GiftOffered;
use App\Events\GiftAccepted;
use App\Events\GiftRejected;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Listeners\BroadcastAdminSignals;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        BookingCompleted::class => [
            [SendBookingNotificationsListener::class, 'handleBookingCompleted'],
            [AwardPointsListener::class, 'handleBookingCompleted'],
            [BroadcastAdminSignals::class, 'handleBookingCompleted'],
        ],
        \App\Events\BookingStatusUpdated::class => [
            [SendBookingNotificationsListener::class, 'handleBookingStatusUpdated'],
        ],
        BookingCancelled::class => [
            [SendBookingNotificationsListener::class, 'handleBookingCancelled'],
            [AwardPointsListener::class, 'handleBookingCancelled'],
        ],
        GiftOffered::class => [
            [GiftNotificationsListener::class, 'onOffered'],
        ],
        GiftAccepted::class => [
            [GiftNotificationsListener::class, 'onAccepted'],
        ],
        GiftRejected::class => [
            [GiftNotificationsListener::class, 'onRejected'],
        ],
        \SocialiteProviders\Manager\SocialiteWasCalled::class => [
            \SocialiteProviders\Apple\AppleExtendSocialite::class.'@handle',
        ],
    ];
}

