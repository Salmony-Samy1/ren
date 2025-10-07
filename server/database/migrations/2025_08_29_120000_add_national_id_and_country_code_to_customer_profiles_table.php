<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('customer_profiles', 'national_id') || !Schema::hasColumn('customer_profiles', 'country_code')) {
            Schema::table('customer_profiles', function (Blueprint $table) {
                if (!Schema::hasColumn('customer_profiles', 'national_id')) {
                    $table->string('national_id')->unique()->after('gender');
                }
                if (!Schema::hasColumn('customer_profiles', 'country_code')) {
                    $table->string('country_code')->after('national_id');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('customer_profiles', 'country_code')) {
                $table->dropColumn('country_code');
            }
            if (Schema::hasColumn('customer_profiles', 'national_id')) {
                // Drop unique index if it exists implicitly
                try {
                    $table->dropUnique(['national_id']);
                } catch (\Throwable $e) {}
                $table->dropColumn('national_id');
            }
        });
    }
};

