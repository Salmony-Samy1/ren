<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services');
            $table->foreignId('user_id')->constrained('users');
            
            $table->decimal('tax', 8, 2)->default(0);
            $table->decimal('subtotal', 8, 2);
            $table->decimal('discount', 8, 2)->default(0);
            $table->decimal('total', 8, 2);
            $table->string('status')->default('pending'); 
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->json('booking_details')->nullable(); 
            $table->string('payment_method')->nullable(); 
            $table->string('transaction_id')->nullable();
            
            $table->boolean('is_paid')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};