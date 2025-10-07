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
        Schema::create('saved_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('customer_id'); // Tap customer ID
            $table->string('card_id'); // Tap card ID
            $table->string('last_four', 4); // آخر 4 أرقام
            $table->string('brand'); // Visa, MasterCard, etc.
            $table->integer('expiry_month'); // شهر انتهاء الصلاحية
            $table->integer('expiry_year'); // سنة انتهاء الصلاحية
            $table->boolean('is_default')->default(false); // البطاقة الافتراضية
            $table->json('tap_response')->nullable(); // استجابة Tap الكاملة
            $table->string('idempotency_key')->nullable()->unique(); // مفتاح منع التكرار
            $table->softDeletes();
            $table->timestamps();

            // فهارس
            $table->index(['user_id', 'customer_id']);
            $table->index(['user_id', 'is_default']);
            $table->unique(['user_id', 'card_id']); // منع تكرار البطاقة لنفس المستخدم
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saved_cards');
    }
};