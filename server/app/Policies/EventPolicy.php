<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Event;
use Illuminate\Auth\Access\HandlesAuthorization;

class EventPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any events.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'event_manager']) || $user->type === 'admin';
    }

    /**
     * Determine whether the user can view the event.
     */
    public function view(User $user, Event $event): bool
    {
        return $user->hasRole(['admin', 'event_manager']) || 
               $user->type === 'admin' ||
               $event->service->user_id === $user->id;
    }

    /**
     * Determine whether the user can create events.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'event_manager']) || $user->type === 'admin';
    }

    /**
     * Determine whether the user can update the event.
     */
    public function update(User $user, Event $event): bool
    {
        return $user->hasRole(['admin', 'event_manager']) || 
               $user->type === 'admin' ||
               $event->service->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the event.
     */
    public function delete(User $user, Event $event): bool
    {
        return $user->hasRole(['admin', 'event_manager']) || $user->type === 'admin';
    }

    /**
     * Determine whether the user can restore the event.
     */
    public function restore(User $user, Event $event): bool
    {
        return $user->hasRole(['admin', 'event_manager']) || $user->type === 'admin';
    }

    /**
     * Determine whether the user can permanently delete the event.
     */
    public function forceDelete(User $user, Event $event): bool
    {
        return $user->hasRole(['admin', 'event_manager']) || $user->type === 'admin';
    }

    /**
     * Determine whether the user can manage events (admin only).
     */
    public function manageEvents(User $user): bool
    {
        return $user->hasRole(['admin', 'event_manager']) || $user->type === 'admin';
    }

    /**
     * Determine whether the user can moderate event content.
     */
    public function moderateContent(User $user): bool
    {
        return $user->hasRole(['admin', 'event_manager']) || $user->type === 'admin';
    }

    /**
     * Determine whether the user can track tickets.
     */
    public function trackTickets(User $user): bool
    {
        return $user->hasRole(['admin', 'event_manager']) || $user->type === 'admin';
    }

    /**
     * Determine whether the user can manage event media.
     */
    public function manageMedia(User $user, Event $event): bool
    {
        return $user->hasRole(['admin', 'event_manager']) || 
               $user->type === 'admin' ||
               $event->service->user_id === $user->id;
    }
}
