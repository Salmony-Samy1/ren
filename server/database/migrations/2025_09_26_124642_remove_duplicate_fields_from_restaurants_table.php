<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration removes duplicate fields from restaurants table
     * that are now handled by restaurant_tables table for better
     * single source of truth architecture.
     */
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            // Remove duplicate pricing fields - these are now handled by restaurant_tables
            $table->dropColumn([
                'standard_price_per_person',    // Duplicate of price_per_person in restaurant_tables
                'standard_table_count',         // Duplicate of quantity in restaurant_tables  
                'standard_capacity_per_table',  // Duplicate of capacity_people in restaurant_tables
                'total_tables',                // Will be calculated dynamically from SUM(quantity) in restaurant_tables
            ]);
        });
    }

    /**
     * Reverse the migrations.
     * 
     * This rollback recreates the duplicate fields for backward compatibility
     * if needed during development/testing phases.
     */
    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            // Recreate the duplicate fields for rollback
            $table->decimal('standard_price_per_person', 12, 2)->nullable()->after('images');
            $table->integer('standard_table_count')->nullable()->after('standard_price_per_person');
            $table->integer('standard_capacity_per_table')->nullable()->after('standard_table_count');
            $table->integer('total_tables')->nullable()->after('standard_capacity_per_table');
        });
    }
};