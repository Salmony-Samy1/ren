<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Page;
use Illuminate\Support\Facades\DB;

class PagesTypesSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            // صفحات قانونية
            [
                'title' => 'شروط الاستخدام',
                'slug' => 'terms-conditions',
                'content' => 'شروط استخدام منصة جاذر للخدمات السياحية. يرجى قراءة هذه الشروط بعناية.',
                'page_type' => 'terms_conditions',
                'target_audience' => 'all',
                'is_published' => true,
                'sort_order' => 1,
                'meta_data' => ['importance' => 'high', 'legal_requirement' => true]
            ],
            [
                'title' => 'سياسة الخصوصية',
                'slug' => 'privacy-policy',
                'content' => 'نحن في جاذر نحترم خصوصيتكم ونلتزم بحماية بياناتكم الشخصية.',
                'page_type' => 'privacy_policy',
                'target_audience' => 'all',
                'is_published' => true,
                'sort_order' => 2,
                'meta_data' => ['importance' => 'high', 'legal_requirement' => true]
            ],

            // صفحات مقدمي الخدمات
            [
                'title' => 'دليل إضافة خدمة جديدة',
                'slug' => 'provider-guide-add-service',
                'content' => 'دليل مفصل لكيفية إضافة خدمة جديدة في منصة جاذر.',
                'page_type' => 'provider_app',
                'target_audience' => 'providers',
                'is_published' => true,
                'sort_order' => 10,
                'meta_data' => ['topic' => 'tutorial', 'difficulty' => 'beginner']
            ],
            [
                'title' => 'كيفية إدارة الحجوزات',
                'slug' => 'provider-guide-bookings',
                'content' => 'تعلم كيفية إدارة وإلغاء الموافقات على الحجوزات.',
                'page_type' => 'provider_frontend',
                'target_audience' => 'providers',
                'is_published' => true,
                'sort_order' => 11,
                'meta_data' => ['topic' => 'management', 'difficulty' => 'intermediate']
            ],

            // صفحات العملاء
            [
                'title' => 'كيفية حجز خدمة',
                'slug' => 'customer-guide-booking',
                'content' => 'دليل شامل لحجز الخدمات في منصة جاذر.',
                'page_type' => 'customer_app',
                'target_audience' => 'customers',
                'is_published' => true,
                'sort_order' => 20,
                'meta_data' => ['topic' => 'tutorial', 'difficulty' => 'beginner']
            ],
            [
                'title' => 'إلغاء أو تعديل الحجز',
                'slug' => 'customee-guide-modify-booking',
                'content' => 'تعلم كيفية إلغاء أو تعديل حجزك.',
                'page_type' => 'customer_frontend',
                'target_audience' => 'customers',
                'is_published' => true,
                'sort_order' => 21,
                'meta_data' => ['topic' => 'modification', 'difficulty' => 'easy']
            ],

            // صفحات عامة
            [
                'title' => 'عن منصة جاذر',
                'slug' => 'about-gathro',
                'content' => 'جاذر هي منصة رائدة في مجال الخدمات السياحية والترفيهية.',
                'page_type' => 'about_us',
                'target_audience' => 'all',
                'is_published' => true,
                'sort_order' => 30,
                'meta_data' => ['company_info' => true, 'showcase' => true]
            ],
            [
                'title' => 'اتصل بنا',
                'slug' => 'contact-us',
                'content' => 'وسائل التواصل مع فريق جاذر. نحن هنا لخدمتكم.',
                'page_type' => 'contact_us',
                'target_audience' => 'all',
                'is_published' => true,
                'sort_order' => 31,
                'meta_data' => ['contact_info' => true, 'urgency' => 'normal']
            ],

            // صفحة المساعدة والدعم
            [
                'title' => 'مركز المساعدة',
                'slug' => 'help-center',
                'content' => 'مركز شامل للمساعدة والدعم التقني.',
                'page_type' => 'help_support',
                'target_audience' => 'registered_users',
                'is_published' => true,
                'sort_order' => 40,
                'meta_data' => ['support_type' => 'general', 'language' => 'ar']
            ],

            // الأسئلة الشائعة
            [
                'title' => 'الأسئلة الشائعة',
                'slug' => 'frequently-asked-questions',
                'content' => 'الإجابات على أكثر الأسئلة شيوعاً حول منصة جاذر.',
                'page_type' => 'faq',
                'target_audience' => 'all',
                'is_published' => true,
                'sort_order' => 50,
                'meta_data' => ['category' => 'general', 'last_updated' => now()]
            ],

            // صفحة الإعلانات
            [
                'title' => 'تحديث النظام الجديد v2.0',
                'slug' => 'system-update-v2',
                'content' => 'نحن سعداء أن نعلن عن إطلاق التحديث الجديد لمنصة جاذر!',
                'page_type' => 'announcements',
                'target_audience' => 'all',
                'is_published' => true,
                'sort_order' => 60,
                'meta_data' => ['release_notes' => true, 'version' => '2.0', 'date' => '2025-10-04']
            ],

            // صفحة أدمن (للاختبار)
            [
                'title' => 'دليل إدارة النظام للإدمن',
                'slug' => 'admin-system-guide',
                'content' => 'دليل شامل لإدارة النظام ومهام الأدمن.',
                'page_type' => 'admin_panel',
                'target_audience' => 'admins',
                'is_published' => true,
                'sort_order' => 1000,
                'meta_data' => ['admin_only' => true, 'sensitive' => true]
            ]
        ];

        foreach ($pages as $pageData) {
            Page::updateOrCreate(
                ['slug' => $pageData['slug']], 
                $pageData
            );
        }

        $this->command->info('تم إنشاء صفحات من جميع الأنواع بنجاح!');
    }
}
