<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BookingPolicy
{
    use HandlesAuthorization;

    public function before(?User $user, string $ability)
    {
        if ($user && $user->type === 'admin') {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
        return false; // non-admin denied by default (admin allowed via before)
    }

    public function view(User $user, Booking $booking): bool
    {
        return $booking->user_id === $user->id || ($user->type === 'provider' && $booking->service && $booking->service->user_id === $user->id);
    }

    public function update(User $user, Booking $booking): bool
    {
        // provider can update status for own service bookings; customer for own
        return $booking->user_id === $user->id || ($user->type === 'provider' && $booking->service && $booking->service->user_id === $user->id);
    }

    public function updateStatus(User $user, Booking $booking): bool
    {
        return $this->update($user, $booking);
    }

    public function cancel(User $user, Booking $booking): bool
    {
        return $booking->user_id === $user->id;
    }
}

