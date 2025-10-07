<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->enum('settlement_status', ['held', 'released', 'refunded', 'rejected'])->default('held')->after('status');
            $table->decimal('held_amount', 12, 2)->default(0)->after('settlement_status');
            $table->timestamp('released_at')->nullable()->after('held_amount');
            $table->timestamp('refunded_at')->nullable()->after('released_at');
            $table->unsignedBigInteger('processed_by')->nullable()->after('refunded_at');
            $table->text('admin_remarks')->nullable()->after('processed_by');
            $table->index(['settlement_status']);
            $table->index(['booking_id']);
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropIndex(['settlement_status']);
            $table->dropIndex(['booking_id']);
            $table->dropColumn(['settlement_status', 'held_amount', 'released_at', 'refunded_at', 'processed_by', 'admin_remarks']);
        });
    }
};

