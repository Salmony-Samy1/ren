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
        // 1) Add national_id to users if not exists
        if (!Schema::hasColumn('users', 'national_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('national_id')->nullable()->after('full_name');
            });
        }

        // 2) For customer_profiles: drop unique index first (if exists), then drop column (if exists)
        if (Schema::hasColumn('customer_profiles', 'national_id')) {
            // Attempt to drop the conventional unique index name before dropping the column (SQLite compatibility)
            try {
                Schema::table('customer_profiles', function (Blueprint $table) {
                    // Using index name is more reliable across drivers
                    $table->dropUnique('customer_profiles_national_id_unique');
                });
            } catch (\Throwable $e) {
                // Ignore if index doesn't exist or driver doesn't support dropUnique by name
                // Some drivers will rebuild table on dropColumn and handle indexes automatically
            }

            Schema::table('customer_profiles', function (Blueprint $table) {
                $table->dropColumn('national_id');
            });
        }

        // 3) For company_profiles: drop unique index first (if exists), then drop column (if exists)
        if (Schema::hasColumn('company_profiles', 'national_id')) {
            try {
                Schema::table('company_profiles', function (Blueprint $table) {
                    $table->dropUnique('company_profiles_national_id_unique');
                });
            } catch (\Throwable $e) {
                // ignore
            }

            Schema::table('company_profiles', function (Blueprint $table) {
                $table->dropColumn('national_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1) Remove national_id from users if exists
        if (Schema::hasColumn('users', 'national_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('national_id');
            });
        }

        // 2) Add national_id back to customer_profiles/company_profiles if missing
        if (!Schema::hasColumn('customer_profiles', 'national_id')) {
            Schema::table('customer_profiles', function (Blueprint $table) {
                $table->string('national_id')->nullable();
            });
        }
        if (!Schema::hasColumn('company_profiles', 'national_id')) {
            Schema::table('company_profiles', function (Blueprint $table) {
                $table->string('national_id')->nullable();
            });
        }
    }
};