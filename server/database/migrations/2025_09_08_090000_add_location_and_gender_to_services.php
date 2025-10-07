<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'address')) {
                $table->string('address')->nullable()->after('name');
            }
            if (!Schema::hasColumn('services', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('address');
            }
            if (!Schema::hasColumn('services', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
            if (!Schema::hasColumn('services', 'place_id')) {
                $table->string('place_id')->nullable()->after('longitude');
            }
            if (!Schema::hasColumn('services', 'city_id')) {
                $table->unsignedBigInteger('city_id')->nullable()->after('place_id');
            }
            if (!Schema::hasColumn('services', 'district')) {
                $table->string('district')->nullable()->after('city_id');
            }
            if (!Schema::hasColumn('services', 'gender_type')) {
                $table->enum('gender_type', ['male','female','both'])->nullable()->after('district');
            }
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'gender_type')) { $table->dropColumn('gender_type'); }
            if (Schema::hasColumn('services', 'district')) { $table->dropColumn('district'); }
            if (Schema::hasColumn('services', 'city_id')) { $table->dropColumn('city_id'); }
            if (Schema::hasColumn('services', 'place_id')) { $table->dropColumn('place_id'); }
            if (Schema::hasColumn('services', 'longitude')) { $table->dropColumn('longitude'); }
            if (Schema::hasColumn('services', 'latitude')) { $table->dropColumn('latitude'); }
            if (Schema::hasColumn('services', 'address')) { $table->dropColumn('address'); }
        });
    }
};

