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
        Schema::table('company_legal_documents', function (Blueprint $table) {
            // حذف العمود إذا كان موجوداً
            if (Schema::hasColumn('company_legal_documents', 'main_service_id')) {
                $table->dropColumn('main_service_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_legal_documents', function (Blueprint $table) {
            // إعادة العمود
            $table->foreignId('main_service_id')->after('company_profile_id')->constrained('main_services');
        });
    }
};
