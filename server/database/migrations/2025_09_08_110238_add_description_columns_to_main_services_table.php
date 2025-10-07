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
            if (!Schema::hasColumn('main_services', 'description')) {
                $table->text('description')->nullable()->after('name_en');
            }

            if (!Schema::hasColumn('main_services', 'description_en')) {
                $table->text('description_en')->nullable()->after('description');
            }
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('main_services', function (Blueprint $table) {
            $table->dropColumn(['description', 'description_en']);
        });
    }

};
