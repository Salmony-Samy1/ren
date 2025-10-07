<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ملاحظة: هذه migration تُضيف الأعمدة المفقودة للجدول pages
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            // تأكد من وجود page_type بعد slug
            if (!Schema::hasColumn('pages', 'page_type')) {
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
            }

            // إضافة target_audience
            if (!Schema::hasColumn('pages', 'target_audience')) {
                $table->enum('target_audience', [
                    'all',                   // للجميع
                    'providers',             // مقدمي الخدمات فقط
                    'customers',             // العملاء فقط
                    'admins',                // الأدمن فقط
                    'guest_users',           // المستخدمين غير المسجلين
                    'registered_users'       // المستخدمين المسجلين فقط
                ])->default('all')->after('page_type');
            }

            // إضافة is_published إذا لم تكن موجودة
            if (!Schema::hasColumn('pages', 'is_published')) {
                $table->boolean('is_published')->default(true)->after('content');
            }

            // إضافة meta_data إذا لم تكن موجودة
            if (!Schema::hasColumn('pages', 'meta_data')) {
                $table->json('meta_data')->nullable()->after('is_published');
            }

            // إضافة sort_order
            if (!Schema::hasColumn('pages', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('meta_data');
            }
        });
    }

    /**
     * إرجاع الأعمدة المضافة
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $columns_to_drop = [];
            
            if (Schema::hasColumn('pages', 'target_audience')) {
                $columns_to_drop[] = 'target_audience';
            }
            if (Schema::hasColumn('pages', 'page_type')) {
                $columns_to_drop[] = 'page_type';
            }
            if (Schema::hasColumn('pages', 'is_published')) {
                $columns_to_drop[] = 'is_published';
            }
            if (Schema::hasColumn('pages', 'meta_data')) {
                $columns_to_drop[] = 'meta_data';
            }
            if (Schema::hasColumn('pages', 'sort_order')) {
                $columns_to_drop[] = 'sort_order';
            }

            if (!empty($columns_to_drop)) {
                $table->dropColumn($columns_to_drop);
            }
        });
    }
};