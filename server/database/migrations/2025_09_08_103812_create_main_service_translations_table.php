<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('main_services', function (Blueprint $table) {
            // Add new column for English name after the existing 'name' column
            $table->string('name_en')->after('name');

            // Add new column for English description after the existing 'description' column
            if (Schema::hasColumn('main_services', 'description')) {
                $table->text('description_en')->nullable()->after('description');
            } else {
                $table->text('description_en')->nullable()->after('name_en');
            }
        });
    }

    public function down(): void
    {
        Schema::table('main_services', function (Blueprint $table) {
            $table->dropColumn(['name_en', 'description_en']);
        });
    }
};