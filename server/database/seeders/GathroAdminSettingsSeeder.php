<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class GathroAdminSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // نقاط وولاء
            ['key' => 'first_booking_points', 'value' => 50, 'description' => 'عدد النقاط الممنوحة عند التسجيل/أول حجز'],
            ['key' => 'loyalty_points', 'value' => 10, 'description' => 'عدد النقاط المكتسبة لكل عملية وفق سياسات الولاء'],
            ['key' => 'review_points', 'value' => 5, 'description' => 'عدد النقاط لمن يكتب مراجعة/تقييم'],
            ['key' => 'referral_points', 'value' => 100, 'description' => 'عدد النقاط للمُحيل عند إحالة ناجحة'],
            ['key' => 'points_to_wallet_rate', 'value' => 0.01, 'description' => 'قيمة الريال لكل نقطة (100 نقطة = 1 ريال)'],
            ['key' => 'min_points_for_conversion', 'value' => 100, 'description' => 'الحد الأدنى لتحويل النقاط إلى محفظة'],
            ['key' => 'points_expiry_days', 'value' => 365, 'description' => 'عدد الأيام لانتهاء صلاحية النقاط المكتسبة'],
            ['key' => 'points_max_redeem_ratio', 'value' => 0.5, 'description' => 'أقصى نسبة من قيمة الحجز يمكن دفعها بالنقاط (مثال: 0.5 = 50%)'],

            // عمولة/تسوية
            ['key' => 'commission_type', 'value' => 'percent', 'description' => 'نوع العمولة: نسبة أو مبلغ ثابت'],
            ['key' => 'commission_amount', 'value' => 10, 'description' => 'قيمة العمولة (٪ أو مبلغ)'],
            ['key' => 'escrow_system_user_id', 'value' => 1, 'description' => 'معرّف مستخدم نظام المحفظة الوسيطة'],
            ['key' => 'provider_payout_trigger', 'value' => 'manual_admin_approval', 'description' => 'طريقة تحرير الأرباح: تلقائي بعد الإتمام أو موافقة إدارية'],

            // OTP & Gateways
            ['key' => 'otp_gateway', 'value' => 'random', 'description' => 'توليد OTP عشوائي أثناء الاختبار وإظهاره في الاستجابة'],
            ['key' => 'sms_gateway', 'value' => 'log', 'description' => 'مزود SMS تجريبي'],

            // البثّ والتنبيهات
            ['key' => 'broadcast_driver', 'value' => 'pusher', 'description' => 'مزود بث الأحداث اللحظية'],
            ['key' => 'fcm_enabled', 'value' => false, 'description' => 'تفعيل FCM من أجل الإشعارات الصامتة'],
            ['key' => 'apns_enabled', 'value' => false, 'description' => 'تفعيل APNs من أجل الإشعارات الصامتة'],

            // محتوى
            ['key' => 'about_page_slug', 'value' => 'about', 'description' => 'Slug صفحة من نحن'],
            ['key' => 'privacy_page_slug', 'value' => 'privacy-policy', 'description' => 'Slug صفحة سياسة الخصوصية'],

            // أخرى
            ['key' => 'national_id_verification_required_for_approval', 'value' => true, 'description' => 'اشتراط التحقق من الهوية الوطنية لمزودي الخدمة قبل الموافقة'],
        ];

        foreach ($settings as $s) {
            \App\Models\Settings::updateOrCreate(
                ['key' => $s['key']],
                ['value' => $s['value'], 'description' => $s['description']]
            );
        }
    }
}

