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
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('name');
            $table->string('commercial_record')->nullable()->after('company_name');
            $table->string('tax_number')->nullable()->after('commercial_record');
            $table->text('description')->nullable()->after('tax_number');
            $table->foreignId('main_service_id')->nullable()->after('description')->constrained('main_services')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->dropForeign(['main_service_id']);
            $table->dropColumn(['company_name', 'commercial_record', 'tax_number', 'description', 'main_service_id']);
        });
    }
};
