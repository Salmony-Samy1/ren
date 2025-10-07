<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->string('property_name');
            $table->string('type');
            $table->string('category');
            $table->json('images')->nullable();
            $table->string('unit_code')->unique();
            $table->integer('area_sqm');
            $table->decimal('down_payment_percentage', 5, 2);
            $table->boolean('is_refundable_insurance');
            $table->string('cancellation_policy');
            $table->text('description');
            $table->string('allowed_category');
            $table->json('room_details');
            $table->json('facilities');
            $table->text('access_instructions');
            $table->string('checkin_time');
            $table->string('checkout_time');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
