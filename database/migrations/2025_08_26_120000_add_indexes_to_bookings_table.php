<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Composite indexes to speed up overlap queries and filters
            $table->index(['service_id', 'status', 'start_date'], 'bookings_service_status_start_idx');
            $table->index(['service_id', 'status', 'end_date'], 'bookings_service_status_end_idx');
            $table->index(['service_id', 'start_date', 'end_date'], 'bookings_service_dates_idx');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_service_status_start_idx');
            $table->dropIndex('bookings_service_status_end_idx');
            $table->dropIndex('bookings_service_dates_idx');
        });
    }
};

