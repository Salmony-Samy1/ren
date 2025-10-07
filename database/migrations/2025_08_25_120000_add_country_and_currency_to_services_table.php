<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'country_code')) {
                $table->enum('country_code', ['SA','BH'])->nullable()->after('place_id');
            }
            if (!Schema::hasColumn('services', 'price_currency')) {
                $table->enum('price_currency', ['SAR','BHD'])->nullable()->after('country_code');
            }
            if (!Schema::hasColumn('services', 'price_amount')) {
                $table->decimal('price_amount', 12, 2)->default(0)->after('price_currency');
            }
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'price_amount')) {
                $table->dropColumn('price_amount');
            }
            if (Schema::hasColumn('services', 'price_currency')) {
                $table->dropColumn('price_currency');
            }
            if (Schema::hasColumn('services', 'country_code')) {
                $table->dropColumn('country_code');
            }
        });
    }
};

