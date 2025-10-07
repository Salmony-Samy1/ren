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
        Schema::create('user_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('action'); // مثل: login, logout, profile_update, booking_create, etc.
            $table->string('description')->nullable(); // وصف النشاط
            $table->string('ip_address')->nullable(); // عنوان IP
            $table->text('user_agent')->nullable(); // معلومات المتصفح/التطبيق
            $table->string('platform')->nullable(); // المنصة (web, mobile, api)
            $table->json('metadata')->nullable(); // بيانات إضافية
            $table->string('status')->default('success'); // success, failed, pending
            $table->timestamp('activity_at')->useCurrent(); // وقت النشاط
            $table->timestamps();
            
            // فهارس للأداء
            $table->index(['user_id', 'activity_at']);
            $table->index(['action', 'activity_at']);
            $table->index('platform');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_activities');
    }
};
