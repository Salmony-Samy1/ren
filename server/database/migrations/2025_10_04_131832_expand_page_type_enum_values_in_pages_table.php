<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * توسيع قيم page_type لتشمل جميع الأنواع المطلوبة
     */
    public function up(): void
    {
        // استخدام raw SQL للتغيير على enum values
        DB::statement("ALTER TABLE pages MODIFY COLUMN page_type ENUM(
            'general', 'legal', 'provider_app', 'customer_app', 'provider_info', 
            'customer_info', 'provider_frontend', 'customer_frontend', 'public_frontend', 
            'admin_panel', 'help_support', 'announcements', 'faq', 'terms_conditions', 
            'privacy_policy', 'about_us', 'contact_us'
        ) DEFAULT 'general'");
    }

    /**
     * إرجاع القيم القديمة
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE pages MODIFY COLUMN page_type ENUM(
            'general', 'legal', 'provider_info'
        ) DEFAULT 'general'");
    }
};