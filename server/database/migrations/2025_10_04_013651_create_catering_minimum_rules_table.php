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
        Schema::create('catering_minimum_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Rule information
            $table->string('rule_name');
            $table->string('region_name');
            $table->string('city');
            
            // Zone coordinates
            $table->decimal('center_lat', 10, 8);
            $table->decimal('center_long', 11, 8);
            $table->decimal('radius_km', 8, 2);
            
            // Order limits
            $table->decimal('min_order_value', 10, 2);
            $table->decimal('delivery_fee', 10, 2);
            $table->decimal('free_delivery_threshold', 10, 2);
            $table->decimal('max_delivery_distance_km', 8, 2);
            
            // Operating hours (stored as JSON for flexibility)
            $table->json('operating_hours');
            $table->json('special_conditions')->nullable();
            
            // Rule status
            $table->boolean('is_active')->default(true)->index();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->index();
            
            // Statistics
            $table->integer('applied_orders_count')->default(0);
            $table->decimal('total_revenue_impact', 12, 2)->default(0);
            
            // Admin tracking
            $table->string('created_by_admin')->nullable();
            $table->text('admin_notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['provider_id', 'is_active']);
            $table->index(['city', 'is_active']);
            $table->index(['region_name', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catering_minimum_rules');
    }
};