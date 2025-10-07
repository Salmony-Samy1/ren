<?php

namespace App\Traits;

use App\Models\UserActivity;

trait LogUserActivity
{
    /**
     * تسجيل نشاط المستخدم
     */
    protected function logActivity($action, $description = null, $metadata = [], $status = 'success')
    {
        $user = auth()->user();
        if (!$user) return;

        try {
            UserActivity::log($user->id, $action, $description, $metadata, $status);
        } catch (\Exception $e) {
            \Log::error('Failed to log user activity', [
                'user_id' => $user->id,
                'action' => $action,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * تسجيل نشاط تحديث الملف الشخصي
     */
    protected function logProfileUpdate($profileType, $updatedFields = [])
    {
        $this->logActivity(
            'profile_update',
            "تم تحديث {$profileType} الملف الشخصي",
            [
                'profile_type' => $profileType,
                'updated_fields' => array_keys($updatedFields),
                'fields_count' => count($updatedFields)
            ]
        );
    }

    /**
     * تسجيل نشاط تغيير كلمة المرور
     */
    protected function logPasswordChange()
    {
        $this->logActivity(
            'password_change',
            'تم تغيير كلمة المرور بنجاح',
            ['security_action' => true]
        );
    }

    /**
     * تسجيل نشاط المتابعة
     */
    protected function logFollow($targetUserId, $action = 'follow')
    {
        $actions = [
            'follow' => 'طلب متابعة مستخدم',
            'unfollow' => 'إلغاء متابعة مستخدم',
            'accept_follow' => 'قبول طلب متابعة',
            'reject_follow' => 'رفض طلب متابعة'
        ];

        $this->logActivity(
            $action,
            $actions[$action] ?? $action,
            ['target_user_id' => $targetUserId]
        );
    }

    /**
     * تسجيل نشاط الإعجاب/المفضلة
     */
    protected function logFavorite($serviceId, $action = 'add_favorite')
    {
        $actions = [
            'add_favorite' => 'إضافة خدمة للمفضلة',
            'remove_favorite' => 'إزالة خدمة من المفضلة',
            'add_wishlist' => 'إضافة نشاط لقائمة الأمنيات',
            'remove_wishlist' => 'إزالة نشاط من قائمة الأمنيات'
        ];

        $this->logActivity(
            $action,
            $actions[$action] ?? $action,
            ['service_id' => $serviceId]
        );
    }

    /**
     * تسجيل نشاط الحجز
     */
    protected function logBooking($bookingId, $action = 'create_booking')
    {
        $actions = [
            'create_booking' => 'إنشاء حجز جديد',
            'update_booking' => 'تحديث الحجز',
            'cancel_booking' => 'إلغاء الحجز',
            'complete_booking' => 'إكمال الحجز'
        ];

        $this->logActivity(
            $action,
            $actions[$action] ?? $action,
            ['booking_id' => $bookingId]
        );
    }

    /**
     * تسجيل نشاط المراجعة
     */
    protected function logReview($reviewId, $action = 'create_review')
    {
        $actions = [
            'create_review' => 'كتابة مراجعة جديدة',
            'update_review' => 'تحديث المراجعة',
            'delete_review' => 'حذف المراجعة'
        ];

        $this->logActivity(
            $action,
            $actions[$action] ?? $action,
            ['review_id' => $reviewId]
        );
    }

    /**
     * تسجيل نشاط الدفع
     */
    protected function logPayment($transactionId, $action = 'process_payment')
    {
        $actions = [
            'process_payment' => 'معالجة دفع',
            'refund_payment' => 'استرداد مبلغ',
            'add_payment_method' => 'إضافة طريقة دفع',
            'remove_payment_method' => 'إزالة طريقة دفع'
        ];

        $this->logActivity(
            $action,
            $actions[$action] ?? $action,
            ['transaction_id' => $transactionId]
        );
    }

    /**
     * تسجيل نشاط الرسائل
     */
    protected function logMessage($conversationId, $action = 'send_message')
    {
        $actions = [
            'send_message' => 'إرسال رسالة',
            'delete_message' => 'حذف رسالة',
            'start_conversation' => 'بدء محادثة جديدة'
        ];

        $this->logActivity(
            $action,
            $actions[$action] ?? $action,
            ['conversation_id' => $conversationId]
        );
    }

    /**
     * تسجيل نشاط الإشعارات
     */
    protected function logNotification($action = 'view_notification')
    {
        $actions = [
            'view_notification' => 'عرض إشعار',
            'mark_notification_read' => 'تحديد إشعار كمقروء',
            'delete_notification' => 'حذف إشعار'
        ];

        $this->logActivity(
            $action,
            $actions[$action] ?? $action
        );
    }

    /**
     * تسجيل نشاط الأمان
     */
    protected function logSecurity($action, $description = null, $metadata = [])
    {
        $this->logActivity(
            $action,
            $description,
            array_merge($metadata, ['security_action' => true]),
            'success'
        );
    }
}
