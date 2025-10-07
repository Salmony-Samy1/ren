<?php

namespace App\Policies;

use App\Models\TableReservation;
use App\Models\User;

class TableReservationPolicy
{
    public function before(?User $user, string $ability)
    {
        // Only admins are allowed for admin controllers
        if ($user && $user->type === 'admin') {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool { return false; }
    public function view(User $user, TableReservation $reservation): bool { return false; }
    public function create(User $user): bool { return false; }
    public function update(User $user, TableReservation $reservation): bool { return false; }
    public function delete(User $user, TableReservation $reservation): bool { return false; }
}

