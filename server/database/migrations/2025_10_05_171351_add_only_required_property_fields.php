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
        // إضافة حقول الموقع فقط لجدول properties
        Schema::table('properties', function (Blueprint $table) {
            $table->string('direction')->nullable()->after('neigbourhood_id');
        });

        // إضافة حقل تدرج المسبح لجدول property_pools
        Schema::table('property_pools', function (Blueprint $table) {
            $table->boolean('is_graduated')->nullable()->after('type');
        });

        // إضافة حقل العدد لجدول property_bathrooms
        Schema::table('property_bathrooms', function (Blueprint $table) {
            $table->integer('count')->nullable()->after('property_id');
        });

        // إضافة حقل السعة لجدول property_living_rooms
        Schema::table('property_living_rooms', function (Blueprint $table) {
            $table->integer('capacity')->nullable()->after('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn('direction');
        });

        Schema::table('property_pools', function (Blueprint $table) {
            $table->dropColumn('is_graduated');
        });

        Schema::table('property_bathrooms', function (Blueprint $table) {
            $table->dropColumn('count');
        });

        Schema::table('property_living_rooms', function (Blueprint $table) {
            $table->dropColumn('capacity');
        });
    }
};