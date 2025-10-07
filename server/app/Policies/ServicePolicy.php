<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServicePolicy
{
    use HandlesAuthorization;

    public function view(User $user, Service $service): bool
    {
        return (int)$user->id === (int)$service->user_id;
    }

    public function update(User $user, Service $service): bool
    {
        return (int)$user->id === (int)$service->user_id;
    }

    public function delete(User $user, Service $service): bool
    {
        return (int)$user->id === (int)$service->user_id;
    }
}