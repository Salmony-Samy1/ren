<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending','paid','failed','cancelled'])->default('pending')->index();
            $table->enum('payment_status', ['pending','completed','failed','refunded','partially_refunded'])->default('pending')->index();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->decimal('payable_total', 12, 2)->default(0);
            $table->string('coupon_code')->nullable();
            $table->decimal('coupon_discount', 12, 2)->default(0);
            $table->integer('points_used')->default(0);
            $table->decimal('points_value', 12, 2)->default(0);
            $table->string('idempotency_key')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id','created_at']);
            $table->unique(['user_id','idempotency_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

