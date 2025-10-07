<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'operating_hours')) {
                $table->json('operating_hours')->nullable()->after('price_currency');
            }
            if (!Schema::hasColumn('services', 'booking_hours')) {
                $table->json('booking_hours')->nullable()->after('operating_hours');
            }
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'booking_hours')) {
                $table->dropColumn('booking_hours');
            }
            if (Schema::hasColumn('services', 'operating_hours')) {
                $table->dropColumn('operating_hours');
            }
        });
    }
};

