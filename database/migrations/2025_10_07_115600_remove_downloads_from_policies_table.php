<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('policies', 'downloads')) {
            Schema::table('policies', function (Blueprint $table) {
                $table->dropColumn('downloads');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('policies', 'downloads')) {
            Schema::table('policies', function (Blueprint $table) {
                $table->unsignedInteger('downloads')->default(0);
            });
        }
    }
};
