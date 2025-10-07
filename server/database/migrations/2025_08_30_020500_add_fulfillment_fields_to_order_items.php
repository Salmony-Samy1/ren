<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'booking_id')) {
                $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete()->after('order_id');
            }
            if (!Schema::hasColumn('order_items', 'fulfillment_status')) {
                $table->enum('fulfillment_status', ['pending','fulfilled','failed'])->default('pending')->after('booking_id');
            }
            if (!Schema::hasColumn('order_items', 'error_message')) {
                $table->string('error_message')->nullable()->after('fulfillment_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'booking_id')) {
                $table->dropConstrainedForeignId('booking_id');
            }
            if (Schema::hasColumn('order_items', 'fulfillment_status')) {
                $table->dropColumn('fulfillment_status');
            }
            if (Schema::hasColumn('order_items', 'error_message')) {
                $table->dropColumn('error_message');
            }
        });
    }
};

