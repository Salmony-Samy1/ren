<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'idempotency_key')) {
                $table->string('idempotency_key', 100)->nullable()->after('transaction_id');
            }
            // Unique per user to allow same key across different users
            $table->unique(['user_id', 'idempotency_key'], 'bookings_user_idempotency_unique');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique('bookings_user_idempotency_unique');
            $table->dropColumn('idempotency_key');
        });
    }
};

