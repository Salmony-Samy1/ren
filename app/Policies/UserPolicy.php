<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function view(User $admin, User $user): bool
    {
        return $admin->type === 'admin';
    }

    public function update(User $admin, User $user): bool
    {
        return $admin->type === 'admin';
    }

    public function updateStatus(User $admin, User $user): bool
    {
        return $admin->type === 'admin';
    }

    public function viewLoginActivities(User $admin, User $user): bool
    {
        return $admin->type === 'admin';
    }

    public function sendNotification(User $admin): bool
    {
        return $admin->type === 'admin';
    }
}

