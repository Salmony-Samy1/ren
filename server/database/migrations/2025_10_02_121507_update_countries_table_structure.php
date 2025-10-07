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
        Schema::table('countries', function (Blueprint $table) {
            // إضافة الحقول المطلوبة
            $table->string('name_ar', 100)->nullable();
            $table->string('name_en', 100)->nullable();
            $table->string('code', 3)->nullable()->unique();
            $table->string('iso_code', 2)->nullable()->unique();
            $table->string('currency_code', 3)->nullable();
            $table->string('currency_name_ar', 50)->nullable();
            $table->string('currency_name_en', 50)->nullable();
            $table->string('currency_symbol', 10)->nullable();
            $table->decimal('exchange_rate', 12, 6)->default(1);
            $table->string('flag_emoji', 10)->nullable();
            $table->string('timezone', 50)->default('Asia/Riyadh');
            $table->integer('sort_order')->default(0);
            
            // فهارس للبحث السريع
            $table->index(['is_active', 'sort_order']);
            $table->index('currency_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'sort_order']);
            $table->dropIndex(['currency_code']);
            $table->dropColumn([
                'name_ar', 'name_en', 'code', 'iso_code', 'currency_code',
                'currency_name_ar', 'currency_name_en', 'currency_symbol',
                'exchange_rate', 'flag_emoji', 'timezone', 'sort_order'
            ]);
        });
    }
};
