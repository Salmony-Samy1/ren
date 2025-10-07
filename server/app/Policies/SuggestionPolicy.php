<?php

namespace App\Policies;

use App\Models\Suggestion;
use App\Models\User;

class SuggestionPolicy
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
        // allow admin users or users with explicit permission suggestions.manage
        return $user->type === 'admin' || $user->can('suggestions.manage');
    }

    public function viewAny(User $user): bool 
    { 
        return $this->canManage($user); 
    }
    
    public function view(User $user, Suggestion $suggestion): bool 
    { 
        return $this->canManage($user); 
    }
    
    public function create(User $user): bool 
    { 
        return $this->canManage($user); 
    }
    
    public function update(User $user, Suggestion $suggestion): bool 
    { 
        return $this->canManage($user); 
    }
    
    public function delete(User $user, Suggestion $suggestion): bool 
    { 
        return $this->canManage($user); 
    }
}
