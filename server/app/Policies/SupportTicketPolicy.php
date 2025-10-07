<?php

namespace App\Policies;

use App\Models\SupportTicket;
use App\Models\User;

class SupportTicketPolicy
{
    public function before(?User $user, string $ability)
    {
        if ($user && $user->type === 'admin') {
            return true;
        }
        return null;
    }

    protected function canManage(User $user): bool
    {
        // allow admin users or users with explicit permission support.manage
        return $user->type === 'admin' || $user->can('support.manage');
    }

    public function viewAny(User $user): bool { return $this->canManage($user); }
    public function view(User $user, SupportTicket $ticket): bool { return $this->canManage($user); }
    public function create(User $user): bool { return $this->canManage($user); }
    public function update(User $user, SupportTicket $ticket): bool { return $this->canManage($user); }
    public function delete(User $user, SupportTicket $ticket): bool { return $this->canManage($user); }
}

