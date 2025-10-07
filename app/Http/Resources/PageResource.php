<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'page_type' => $this->page_type,
            'page_type_label' => $this->getPageTypeLabel(),
            'target_audience' => $this->target_audience,
            'target_audience_label' => $this->getTargetAudienceLabel(),
            'is_published' => $this->is_published,
            'status' => $this->status,
            'sort_order' => $this->sort_order,
            'meta_data' => $this->meta_data,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * الحصول على التسمية الوصفية لنوع الصفحة
     */
    private function getPageTypeLabel(): string
    {
        $labels = [
            'general' => 'عام - محتوى مفتوح للجميع',
            'legal' => 'قانوني - سياسات وشروط',
            'provider_app' => 'تطبيق مقدم الخدمة',
            'customer_app' => 'تطبيق العميل',
            'provider_info' => 'معلومات مقدم الخدمة',
            'customer_info' => 'معلومات العميل',
            'provider_frontend' => 'فرونت اند لمقدم الخدمة',
            'customer_frontend' => 'فرونت اند للعميل',
            'public_frontend' => 'فرونت اند عام للجميع',
            'admin_panel' => 'لوحة تحكم الأدمن',
            'help_support' => 'مساعدة ودعم',
            'announcements' => 'إعلانات وإشعارات',
            'faq' => 'أسئلة شائعة',
            'terms_conditions' => 'شروط الاستخدام',
            'privacy_policy' => 'سياسة الخصوصية',
            'about_us' => 'عنا',
            'contact_us' => 'اتصل بنا'
        ];

        return $labels[$this->page_type] ?? $this->page_type;
    }

    /**
     * الحصول على التسمية الوصفية للجمهور المستهدف
     */
    private function getTargetAudienceLabel(): string
    {
        $labels = [
            'all' => 'للجميع',
            'providers' => 'مقدمي الخدمات فقط',
            'customers' => 'العملاء فقط',
            'admins' => 'الأدمن فقط',
            'guest_users' => 'المستخدمين غير المسجلين',
            'registered_users' => 'المستخدمين المسجلين فقط'
        ];

        return $labels[$this->target_audience] ?? $this->target_audience;
    }
}
