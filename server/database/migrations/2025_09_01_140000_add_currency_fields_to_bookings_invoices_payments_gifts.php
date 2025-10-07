<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'currency')) {
                $table->string('currency', 8)->nullable()->after('service_id');
            }
            if (!Schema::hasColumn('bookings', 'wallet_currency')) {
                $table->string('wallet_currency', 8)->nullable()->after('currency');
            }
            if (!Schema::hasColumn('bookings', 'total_wallet_currency')) {
                $table->decimal('total_wallet_currency', 12, 2)->nullable()->after('total');
            }
            if (!Schema::hasColumn('bookings', 'idempotency_key')) {
                $table->string('idempotency_key', 100)->nullable()->after('transaction_id');
                $table->index('idempotency_key');
            }
        });

        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'currency')) {
                $table->string('currency', 8)->nullable()->after('commission_amount');
            }
            if (!Schema::hasColumn('invoices', 'provider_amount')) {
                $table->decimal('provider_amount', 12, 2)->nullable()->after('commission_amount');
            }
            if (!Schema::hasColumn('invoices', 'platform_amount')) {
                $table->decimal('platform_amount', 12, 2)->nullable()->after('provider_amount');
            }
            if (!Schema::hasColumn('invoices', 'points_used')) {
                $table->integer('points_used')->default(0)->after('platform_amount');
            }
            if (!Schema::hasColumn('invoices', 'points_value')) {
                $table->decimal('points_value', 12, 2)->default(0)->after('points_used');
            }
        });

        Schema::table('payment_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_transactions', 'currency')) {
                $table->string('currency', 8)->nullable()->after('amount');
            }
        });

        Schema::table('gifts', function (Blueprint $table) {
            if (!Schema::hasColumn('gifts', 'sender_currency')) {
                $table->string('sender_currency', 8)->nullable()->after('amount');
            }
            if (!Schema::hasColumn('gifts', 'recipient_currency')) {
                $table->string('recipient_currency', 8)->nullable()->after('sender_currency');
            }
            if (!Schema::hasColumn('gifts', 'amount_recipient_currency')) {
                $table->decimal('amount_recipient_currency', 12, 2)->nullable()->after('recipient_currency');
            }
        });
    }

    public function down(): void
    {
        Schema::table('gifts', function (Blueprint $table) {
            foreach (['amount_recipient_currency','recipient_currency','sender_currency'] as $c) {
                if (Schema::hasColumn('gifts', $c)) $table->dropColumn($c);
            }
        });
        Schema::table('payment_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('payment_transactions', 'currency')) $table->dropColumn('currency');
        });
        Schema::table('invoices', function (Blueprint $table) {
            foreach (['currency','provider_amount','platform_amount','points_used','points_value'] as $c) {
                if (Schema::hasColumn('invoices', $c)) $table->dropColumn($c);
            }
        });
        Schema::table('bookings', function (Blueprint $table) {
            foreach (['currency','wallet_currency','total_wallet_currency','idempotency_key'] as $c) {
                if (Schema::hasColumn('bookings', $c)) $table->dropColumn($c);
            }
        });
    }
};

