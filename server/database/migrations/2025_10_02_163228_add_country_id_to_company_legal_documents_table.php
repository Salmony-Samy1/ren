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
            // Check if main_service_id column exists before using it as reference
            if (Schema::hasColumn('company_legal_documents', 'main_service_id')) {
                $table->foreignId('country_id')->nullable()->after('main_service_id')->constrained('countries');
                $table->index(['company_profile_id', 'main_service_id', 'country_id'], 'cld_company_service_country');
            } else {
                // If main_service_id doesn't exist, add country_id after company_profile_id
                $table->foreignId('country_id')->nullable()->after('company_profile_id')->constrained('countries');
                $table->index(['company_profile_id', 'country_id'], 'cld_company_country');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_legal_documents', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
            
            // Drop the appropriate index based on what was created
            if (Schema::hasColumn('company_legal_documents', 'main_service_id')) {
                $table->dropIndex('cld_company_service_country');
            } else {
                $table->dropIndex('cld_company_country');
            }
            
            $table->dropColumn('country_id');
        });
    }
};