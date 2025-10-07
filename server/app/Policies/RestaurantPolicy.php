<?php

namespace App\Policies;

use App\Models\Restaurant;
use App\Models\User;

class RestaurantPolicy
{
    public function before(?User $user, string $ability)
    {
        if ($user && $user->type === 'admin') {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool { return false; }
    public function view(User $user, Restaurant $restaurant): bool { return false; }
    public function create(User $user): bool { return false; }
    public function update(User $user, Restaurant $restaurant): bool
    {
        return (int)$user->id === (int)$restaurant->service->user_id;
    }
    public function delete(User $user, Restaurant $restaurant): bool { return false; }
}

