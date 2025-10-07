<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            foreach (['address','latitude','longitude','place_id','country_code','price_currency','price_amount'] as $col) {
                if (Schema::hasColumn('services', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'address')) { $table->string('address')->nullable()->after('user_id'); }
            if (!Schema::hasColumn('services', 'latitude')) { $table->decimal('latitude', 10, 8)->nullable()->after('user_id'); }
            if (!Schema::hasColumn('services', 'longitude')) { $table->decimal('longitude', 11, 8)->nullable()->after('latitude'); }
            if (!Schema::hasColumn('services', 'place_id')) { $table->string('place_id')->nullable()->after('address'); }
            if (!Schema::hasColumn('services', 'country_code')) { $table->string('country_code', 2)->nullable()->after('place_id'); }
            if (!Schema::hasColumn('services', 'price_currency')) { $table->string('price_currency', 3)->nullable()->after('country_code'); }
            if (!Schema::hasColumn('services', 'price_amount')) { $table->decimal('price_amount', 12, 2)->default(0)->after('price_currency'); }
        });
    }
};

