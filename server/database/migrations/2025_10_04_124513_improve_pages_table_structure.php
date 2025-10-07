<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->enum('page_type', [
                'general',           // عام - محتوى مفتوح للجميع
                'legal',            // قانوني - سياسات وشروط
                'provider_app',     // تطبيق مقدم الخدمة
                'customer_app',     // تطبيق العميل  
                'provider_info',    // معلومات مقدم الخدمة
                'customer_info',    // معلومات العميل
                'provider_frontend',// فرونت اند لمقدم الخدمة
                'customer_frontend',// فرونت اند للعميل
                'public_frontend',  // فرونت اند عام للجميع
                'admin_panel',      // لوحة تحكم الأدمن
                'help_support',     // مساعدة ودعم
                'announcements',    // إعلانات وإشعارات
                'faq',              // أسئلة شائعة
                'terms_conditions', // شروط الاستخدام
                'privacy_policy',   // سياسة الخصوصية
                'about_us',         // عنا
                'contact_us'        // اتصل بنا
            ])->default('general')->after('slug');
            $table->enum('target_audience', [
                'all',           // للجميع
                'providers',     // مقدمي الخدمات فقط
                'customers',     // العملاء فقط
                'admins',        // الأدمن فقط
                'guest_users',   // المستخدمين غير المسجلين
                'registered_users' // المستخدمين المسجلين فقط
            ])->default('all')->after('page_type');
            
            $table->boolean('is_published')->default(true)->after('content');
            $table->json('meta_data')->nullable()->after('is_published');
            $table->integer('sort_order')->default(0)->after('meta_data'); // لترتيب الصفحات
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['page_type', 'is_published', 'meta_data']);
        });
    }
};
