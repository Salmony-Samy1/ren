<?php

namespace App\Policies;

use App\Models\Alert;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AlertPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Alert $alert): bool
    {
        // الأدمن يمكنه رؤية جميع التحذيرات
        if ($user->hasRole('admin')) {
            return true;
        }
        
        // المستخدم يمكنه رؤية تحذيراته فقط
        return $user->id === $alert->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Alert $alert): bool
    {
        // الأدمن يمكنه تحديث جميع التحذيرات
        if ($user->hasRole('admin')) {
            return true;
        }
        
        // المستخدم يمكنه تحديث تحذيراته فقط إذا لم تكن مقروءة
        return $user->id === $alert->user_id && !$alert->is_read;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Alert $alert): bool
    {
        // الأدمن يمكنه حذف جميع التحذيرات
        if ($user->hasRole('admin')) {
            return true;
        }
        
        // المستخدم لا يمكنه حذف تحذيراته
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Alert $alert): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Alert $alert): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can acknowledge the alert.
     */
    public function acknowledge(User $user, Alert $alert): bool
    {
        // الأدمن يمكنه تأكيد جميع التحذيرات
        if ($user->hasRole('admin')) {
            return true;
        }
        
        // المستخدم يمكنه تأكيد تحذيراته فقط
        return $user->id === $alert->user_id;
    }

    /**
     * Determine whether the user can mark the alert as read.
     */
    public function markAsRead(User $user, Alert $alert): bool
    {
        // المستخدم يمكنه وضع علامة مقروء على تحذيراته فقط
        return $user->id === $alert->user_id;
    }
}