<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvoicePolicy
{
    use HandlesAuthorization;

    public function view(User $user, Invoice $invoice): bool
    {
        return (int)$invoice->user_id === (int)$user->id || $user->type === 'admin';
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->type === 'admin';
    }

    public function cancel(User $user, Invoice $invoice): bool
    {
        return (int)$invoice->user_id === (int)$user->id || $user->type === 'admin';
    }
}

