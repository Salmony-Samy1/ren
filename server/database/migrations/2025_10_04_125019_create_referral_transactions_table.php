<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('referral_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->onDelete('cascade'); // المحيل
            $table->foreignId('referred_user_id')->constrained('users')->onDelete('cascade'); // المستخدم المحال
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->onDelete('set null');
            $table->decimal('commission_amount', 8, 2);
            $table->decimal('commission_rate', 5, 2); // نسبة العمولة المطبقة
            $table->enum('commission_type', ['booking_commission', 'registration_bonus'])->default('booking_commission');
            $table->enum('status', ['pending', 'approved', 'paid', 'cancelled'])->default('pending');
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['referrer_id', 'status']);
            $table->index(['referred_user_id']);
            $table->index(['booking_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_transactions');
    }
};
