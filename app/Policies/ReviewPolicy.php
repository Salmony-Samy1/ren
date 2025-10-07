<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReviewPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Review $review): bool
    {
        return $review->user_id === $user->id || $user->type === 'admin' || ($user->type === 'provider' && $review->service && $review->service->user_id === $user->id);
    }

    public function update(User $user, Review $review): bool
    {
        return $review->user_id === $user->id || $user->type === 'admin';
    }

    public function delete(User $user, Review $review): bool
    {
        return $review->user_id === $user->id || $user->type === 'admin';
    }
}

