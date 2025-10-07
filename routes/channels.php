<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Customer's own bookings stream
Broadcast::channel('Bookings.User.{userId}', function ($user, $userId) {
    // Only customers can subscribe to their own bookings channel
    $ok = ($user->type === 'customer') && ((int) $user->id === (int) $userId);
    return $ok;
});
Broadcast::channel('private-Bookings.User.{userId}', function ($user, $userId) {
    // Only customers can subscribe to their own bookings channel
    $ok = ($user->type === 'customer') && ((int) $user->id === (int) $userId);
    abort_if(! $ok, 403);
    return true;
});

// Provider service bookings stream (provider must own the service)
Broadcast::channel('Services.{serviceId}.Bookings', function ($user, $serviceId) {
    $service = \App\Models\Service::find($serviceId);
    if (! $service) {
        return false;
    }
    if (! ($user->type === 'admin' || $service->user_id === $user->id)) {
        return false;
    }
    return true;
});
Broadcast::channel('private-Services.{serviceId}.Bookings', function ($user, $serviceId) {
    $service = \App\Models\Service::find($serviceId);
    abort_if(! $service, 403);
    $ok = $user->type === 'admin' || $service->user_id === $user->id;
    abort_if(! $ok, 403);
    return true;
});

// Conversations private channel: only participants can listen
Broadcast::channel('Conversations.{conversationId}', function ($user, $conversationId) {
    $conv = \App\Models\Conversation::find($conversationId);
    if (! $conv) { return false; }
    $ok = in_array($user->id, [$conv->user1_id, $conv->user2_id], true);
    if (! $ok) { abort(403); }
    return true;
});

// Admin dashboard private channel (admins only)
Broadcast::channel('Admin.Dashboard', function ($user) {
    return $user->type === 'admin';
});

// Admin dashboard presence channel (admins only)
Broadcast::channel('presence-Admin.Dashboard', function ($user) {
    if ($user->type !== 'admin') { return false; }
    return ['id' => $user->id, 'name' => $user->full_name ?? ('Admin#'.$user->id)];
});

