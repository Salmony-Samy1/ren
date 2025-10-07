<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // إضافة 'test' إلى ENUM values لعمود payment_method
        DB::statement("ALTER TABLE payment_transactions MODIFY COLUMN payment_method ENUM('wallet','apple_pay','visa','mada','samsung_pay','benefit','stcpay','tap_card','tap_benefit','tap_apple_pay','tap_google_pay','tap_benefitpay','test')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // إزالة 'test' من ENUM values
        DB::statement("ALTER TABLE payment_transactions MODIFY COLUMN payment_method ENUM('wallet','apple_pay','visa','mada','samsung_pay','benefit','stcpay','tap_card','tap_benefit','tap_apple_pay','tap_google_pay','tap_benefitpay')");
    }
};