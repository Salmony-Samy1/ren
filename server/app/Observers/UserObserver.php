<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Str;

class UserObserver
{
    public function creating(User $user): void
    {
        $user->uuid = (string) Str::uuid();
        if (empty($user->public_id)) {
            $user->public_id = $this->generatePublicId();
        }
    }

    private function generatePublicId(): string
    {
        do {
            $candidate = 'gathro_user_' . random_int(10000, 999999);
        } while (User::where('public_id', $candidate)->exists());
        return $candidate;
    }
}
