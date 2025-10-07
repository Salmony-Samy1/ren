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
        // إضافة الحقول المفقودة إلى جدول company_profiles
        Schema::table('company_profiles', function (Blueprint $table) {
            // التحقق من وجود الحقول قبل إضافتها
            if (!Schema::hasColumn('company_profiles', 'nationality_id')) {
                $table->foreignId('nationality_id')->nullable()->after('owner')->constrained('nationalities')->nullOnDelete();
            }
            if (!Schema::hasColumn('company_profiles', 'iban')) {
                $table->string('iban', 34)->nullable()->after('commercial_record');
            }
            if (!Schema::hasColumn('company_profiles', 'tourism_license_number')) {
                $table->string('tourism_license_number')->nullable()->after('iban');
            }
            if (!Schema::hasColumn('company_profiles', 'kyc_id')) {
                $table->string('kyc_id')->nullable()->after('tourism_license_number');
            }
            if (!Schema::hasColumn('company_profiles', 'region_id')) {
                $table->foreignId('region_id')->nullable()->after('city_id')->constrained('regions')->nullOnDelete();
            }
        });

        // إضافة الحقول المفقودة إلى جدول users
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'nationality')) {
                $table->string('nationality')->nullable()->after('country_code');
            }
            if (!Schema::hasColumn('users', 'kyc_id')) {
                $table->string('kyc_id')->nullable()->after('nationality');
            }
        });

        // إضافة حقل start_date إلى جدول company_legal_documents
        Schema::table('company_legal_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('company_legal_documents', 'start_date')) {
                $table->timestamp('start_date')->nullable()->after('file_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->dropForeign(['nationality_id']);
            $table->dropForeign(['region_id']);
            $table->dropColumn(['nationality_id', 'iban', 'tourism_license_number', 'kyc_id', 'region_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nationality', 'kyc_id']);
        });

        Schema::table('company_legal_documents', function (Blueprint $table) {
            $table->dropColumn('start_date');
        });
    }
};
