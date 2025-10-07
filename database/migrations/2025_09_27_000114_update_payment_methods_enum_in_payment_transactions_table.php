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
        // SQLite لا يدعم MODIFY COLUMN، لذا سنستخدم طريقة مختلفة
        if (DB::getDriverName() === 'sqlite') {
            // إعادة إنشاء الجدول مع enum محدث
            Schema::dropIfExists('payment_transactions');
            Schema::create('payment_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('booking_id')->nullable()->constrained()->onDelete('cascade');
                $table->decimal('amount', 10, 2);
                $table->enum('payment_method', [
                    'wallet', 'apple_pay', 'visa', 'mada', 'samsung_pay', 'benefit', 'stcpay',
                    'tap_card', 'tap_benefit', 'tap_apple_pay', 'tap_google_pay', 'tap_benefitpay'
                ]);
                $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('pending');
                $table->string('transaction_id')->nullable()->unique();
                $table->json('gateway_response')->nullable();
                $table->string('idempotency_key')->nullable()->unique();
                $table->json('metadata')->nullable();
                $table->timestamps();
                
                $table->index(['user_id', 'status']);
                $table->index(['payment_method', 'status']);
                $table->index('transaction_id');
            });
        } else {
            // MySQL/PostgreSQL
            DB::statement("ALTER TABLE payment_transactions MODIFY COLUMN payment_method ENUM(
                'wallet', 
                'apple_pay', 
                'visa', 
                'mada', 
                'samsung_pay', 
                'benefit', 
                'stcpay',
                'tap_card',
                'tap_benefit',
                'tap_apple_pay',
                'tap_google_pay',
                'tap_benefitpay'
            )");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // إرجاع enum إلى حالته الأصلية
        DB::statement("ALTER TABLE payment_transactions MODIFY COLUMN payment_method ENUM(
            'wallet', 
            'apple_pay', 
            'visa', 
            'mada', 
            'samsung_pay', 
            'benefit', 
            'stcpay'
        )");
    }
};