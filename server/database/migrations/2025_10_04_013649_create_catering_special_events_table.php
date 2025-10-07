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
        Schema::create('catering_special_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Event basic information
            $table->string('event_name');
            $table->enum('event_type', ['wedding', 'conference', 'gala', 'corporate', 'charity', 'private_celebration'])->index();
            $table->text('description')->nullable();
            
            // Client information
            $table->string('client_name');
            $table->string('client_phone');
            $table->string('client_email')->nullable();
            
            // Event details
            $table->timestamp('event_datetime');
            $table->integer('guest_count');
            $table->decimal('estimated_budget', 12, 2)->nullable();
            $table->decimal('confirmed_budget', 12, 2)->nullable();
            
            $table->enum('status', ['inquiry', 'planning', 'confirmed', 'in_progress', 'completed', 'cancelled'])->default('inquiry')->index();
            $table->integer('progress_percentage')->default(0);
            
            // Location information
            $table->string('venue_name')->nullable();
            $table->text('full_address');
            $table->string('event_city');
            $table->decimal('event_lat', 10, 8)->nullable();
            $table->decimal('event_long', 11, 8)->nullable();
            
            // Timeline and preparation
            $table->timestamp('planning_start_date')->nullable();
            $table->integer('preparation_days')->nullable();
            
            // Special requirements and menu
            $table->json('special_requirements')->nullable();
            $table->json('menu_items')->nullable();
            $table->json('timeline')->nullable();
            $table->json('contact_persons')->nullable();
            
            // Admin fields
            $table->text('admin_notes')->nullable();
            $table->string('created_by_admin')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['status', 'event_type']);
            $table->index(['provider_id', 'status']);
            $table->index(['event_datetime', 'status']);
            $table->index('event_city');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catering_special_events');
    }
};