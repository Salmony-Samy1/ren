<?php

namespace App\Policies;

use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentTransactionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, PaymentTransaction $transaction): bool
    {
        return $transaction->user_id === $user->id || $user->type === 'admin';
    }
}

