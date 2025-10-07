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
        Schema::table('main_services', function (Blueprint $table) {
            $table->foreignId('default_country_id')->nullable()->after('description_en')->constrained('countries');
            $table->index('default_country_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('main_services', function (Blueprint $table) {
            $table->dropForeign(['default_country_id']);
            $table->dropIndex(['default_country_id']);
            $table->dropColumn('default_country_id');
        });
    }
};