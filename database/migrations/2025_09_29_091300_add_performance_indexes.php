<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Add indexes for search performance
            $table->index(['is_approved', 'category_id'], 'services_approved_category_idx');
            $table->index(['is_approved', 'created_at'], 'services_approved_created_idx');
            $table->index(['latitude', 'longitude'], 'services_location_idx');
            $table->index(['rating_avg'], 'services_rating_idx');
        });

        Schema::table('support_tickets', function (Blueprint $table) {
            // Add index for SLA alerts performance
            $table->index(['status', 'created_at'], 'support_tickets_status_created_idx');
            $table->boolean('sla_alert_sent')->default(false)->after('status');
        });

        Schema::table('events', function (Blueprint $table) {
            // Add index for price sorting
            $table->index(['service_id', 'base_price'], 'events_service_price_idx');
        });

        Schema::table('restaurants', function (Blueprint $table) {
            // Add index for restaurant queries
            $table->index(['service_id'], 'restaurants_service_idx');
        });

        Schema::table('restaurant_tables', function (Blueprint $table) {
            // Add index for table queries
            $table->index(['restaurant_id', 'price_per_person'], 'restaurant_tables_price_idx');
        });

        Schema::table('properties', function (Blueprint $table) {
            // Add index for property queries
            $table->index(['service_id', 'nightly_price'], 'properties_service_price_idx');
            $table->index(['latitude', 'longitude'], 'properties_location_idx');
        });

        Schema::table('caterings', function (Blueprint $table) {
            // Add index for catering queries
            $table->index(['service_id'], 'caterings_service_idx');
        });

        Schema::table('catering_items', function (Blueprint $table) {
            // Add index for catering items
            $table->index(['catering_id', 'price'], 'catering_items_price_idx');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex('services_approved_category_idx');
            $table->dropIndex('services_approved_created_idx');
            $table->dropIndex('services_location_idx');
            $table->dropIndex('services_rating_idx');
        });

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropIndex('support_tickets_status_created_idx');
            $table->dropColumn('sla_alert_sent');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('events_service_price_idx');
        });

        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropIndex('restaurants_service_idx');
        });

        Schema::table('restaurant_tables', function (Blueprint $table) {
            $table->dropIndex('restaurant_tables_price_idx');
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->dropIndex('properties_service_price_idx');
            $table->dropIndex('properties_location_idx');
        });

        Schema::table('caterings', function (Blueprint $table) {
            $table->dropIndex('caterings_service_idx');
        });

        Schema::table('catering_items', function (Blueprint $table) {
            $table->dropIndex('catering_items_price_idx');
        });
    }
};
