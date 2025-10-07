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
        Schema::create('catering_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Customer information
            $table->string('customer_name');
            $table->string('customer_phone'); 
            $table->string('customer_email')->nullable();
            
            // Delivery information
            $table->timestamp('scheduled_delivery_at');
            $table->timestamp('actual_delivery_at')->nullable();
            $table->enum('status', ['scheduled', 'preparing', 'out_for_delivery', 'delivered', 'cancelled'])->default('scheduled');
            
            // Address information
            $table->text('delivery_address');
            $table->string('delivery_street')->nullable();
            $table->string('delivery_building')->nullable();
            $table->string('delivery_district')->nullable();
            $table->string('delivery_city');
            $table->decimal('delivery_lat', 10, 8)->nullable();
            $table->decimal('delivery_long', 11, 8)->nullable();
            
            // Delivery details
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->boolean('free_delivery_applied')->default(false);
            $table->integer('estimated_duration_minutes')->nullable();
            $table->text('delivery_notes')->nullable();
            $table->text('admin_notes')->nullable();
            
            // Driver information
            $table->string('driver_id')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('driver_phone')->nullable();
            $table->string('vehicle_plate')->nullable();
            
            // Delivery person
            $table->string('delivery_person_name')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['status', 'scheduled_delivery_at']);
            $table->index(['provider_id', 'status']);
            $table->index('delivery_city');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catering_deliveries');
    }
};