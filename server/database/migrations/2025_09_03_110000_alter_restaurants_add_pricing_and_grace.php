<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->decimal('standard_price_per_person', 12, 2)->nullable()->after('images');
            $table->integer('standard_table_count')->nullable()->after('standard_price_per_person');
            $table->decimal('vip_price_per_person', 12, 2)->nullable()->after('standard_table_count');
            $table->integer('grace_period_minutes')->default(15)->after('working_hours');
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn(['standard_price_per_person','standard_table_count','vip_price_per_person','grace_period_minutes']);
        });
    }
};

