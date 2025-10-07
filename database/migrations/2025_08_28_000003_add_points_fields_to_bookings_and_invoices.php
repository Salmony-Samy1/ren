<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedInteger('points_used')->default(0)->after('discount');
            $table->decimal('points_value', 12, 2)->default(0)->after('points_used');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedInteger('points_used')->default(0)->after('discount_amount');
            $table->decimal('points_value', 12, 2)->default(0)->after('points_used');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['points_used','points_value']);
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['points_used','points_value']);
        });
    }
};

