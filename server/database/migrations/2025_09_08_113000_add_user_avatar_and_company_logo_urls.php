<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'avatar_url')) {
                $table->string('avatar_url')->nullable()->after('phone');
            }
        });
        Schema::table('company_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('company_profiles', 'company_logo_url')) {
                $table->string('company_logo_url')->nullable()->after('company_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'avatar_url')) { $table->dropColumn('avatar_url'); }
        });
        Schema::table('company_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('company_profiles', 'company_logo_url')) { $table->dropColumn('company_logo_url'); }
        });
    }
};

