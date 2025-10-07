<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\Event;
use App\Models\SupportTicket;
use App\Models\Suggestion;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Event::class => \App\Policies\EventPolicy::class,
        SupportTicket::class => \App\Policies\SupportTicketPolicy::class,
        Suggestion::class => \App\Policies\SuggestionPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Event Management Gates
        Gate::define('manage-events', function (User $user) {
            return $user->hasRole(['admin', 'event_manager']) || $user->type === 'admin';
        });

        Gate::define('create-events', function (User $user) {
            return $user->hasRole(['admin', 'event_manager']) || $user->type === 'admin';
        });

        Gate::define('edit-events', function (User $user, Event $event = null) {
            if ($user->hasRole(['admin', 'event_manager']) || $user->type === 'admin') {
                return true;
            }
            
            if ($event) {
                return $event->service->user_id === $user->id;
            }
            
            return false;
        });

        Gate::define('delete-events', function (User $user) {
            return $user->hasRole(['admin', 'event_manager']) || $user->type === 'admin';
        });

        Gate::define('moderate-event-content', function (User $user) {
            return $user->hasRole(['admin', 'event_manager']) || $user->type === 'admin';
        });

        Gate::define('track-event-tickets', function (User $user) {
            return $user->hasRole(['admin', 'event_manager']) || $user->type === 'admin';
        });

        Gate::define('manage-event-media', function (User $user, Event $event = null) {
            if ($user->hasRole(['admin', 'event_manager']) || $user->type === 'admin') {
                return true;
            }
            
            if ($event) {
                return $event->service->user_id === $user->id;
            }
            
            return false;
        });

        // Additional security gates
        Gate::define('view-event-analytics', function (User $user) {
            return $user->hasRole(['admin', 'event_manager']) || $user->type === 'admin';
        });

        Gate::define('export-event-data', function (User $user) {
            return $user->hasRole(['admin', 'event_manager']) || $user->type === 'admin';
        });

        Gate::define('bulk-event-actions', function (User $user) {
            return $user->hasRole(['admin', 'event_manager']) || $user->type === 'admin';
        });
    }
}