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
            // إضافة العمود الجديد إذا لم يكن موجوداً
            if (!Schema::hasColumn('company_legal_documents', 'main_service_required_document_id')) {
                $table->foreignId('main_service_required_document_id')->nullable()->after('company_profile_id')
                    ->constrained('main_service_required_documents', 'id', 'cld_msrd_foreign')->cascadeOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_legal_documents', function (Blueprint $table) {
            $table->dropForeign('cld_msrd_foreign');
            $table->dropColumn('main_service_required_document_id');
        });
    }
};
