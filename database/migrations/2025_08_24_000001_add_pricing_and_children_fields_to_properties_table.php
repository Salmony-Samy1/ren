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
        Schema::table('properties', function (Blueprint $table) {
            if (!Schema::hasColumn('properties', 'nightly_price')) {
                $table->decimal('nightly_price', 10, 2)->default(0)->after('access_instructions');
            }
            if (!Schema::hasColumn('properties', 'max_adults')) {
                $table->integer('max_adults')->nullable()->after('nightly_price');
            }
            if (!Schema::hasColumn('properties', 'max_children')) {
                $table->integer('max_children')->nullable()->after('max_adults');
            }
            if (!Schema::hasColumn('properties', 'child_free_until_age')) {
                $table->integer('child_free_until_age')->nullable()->after('max_children');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            if (Schema::hasColumn('properties', 'child_free_until_age')) {
                $table->dropColumn('child_free_until_age');
            }
            if (Schema::hasColumn('properties', 'max_children')) {
                $table->dropColumn('max_children');
            }
            if (Schema::hasColumn('properties', 'max_adults')) {
                $table->dropColumn('max_adults');
            }
            if (Schema::hasColumn('properties', 'nightly_price')) {
                $table->dropColumn('nightly_price');
            }
        });
    }
};

