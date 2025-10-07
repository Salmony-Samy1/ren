<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'start_date')) {
                $table->dropColumn('start_date');
            }
            if (Schema::hasColumn('events', 'end_date')) {
                $table->dropColumn('end_date');
            }
            if (!Schema::hasColumn('events', 'age_min')) {
                $table->unsignedSmallInteger('age_min')->nullable()->after('language');
            }
            if (!Schema::hasColumn('events', 'age_max')) {
                $table->unsignedSmallInteger('age_max')->nullable()->after('age_min');
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'age_max')) { $table->dropColumn('age_max'); }
            if (Schema::hasColumn('events', 'age_min')) { $table->dropColumn('age_min'); }
            if (!Schema::hasColumn('events', 'start_date')) { $table->date('start_date')->nullable()->after('max_individuals'); }
            if (!Schema::hasColumn('events', 'end_date')) { $table->date('end_date')->nullable()->after('start_date'); }
        });
    }
};

