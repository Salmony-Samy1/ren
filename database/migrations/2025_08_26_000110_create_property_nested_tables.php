<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_bedrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->unsignedInteger('beds_count')->default(1);
            $table->boolean('is_master')->default(false);
            $table->timestamps();
        });

        Schema::create('property_living_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->string('type'); // main, outdoor, etc.
            $table->timestamps();
        });

        Schema::create('property_pools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->decimal('length_m', 5, 2)->nullable();
            $table->decimal('width_m', 5, 2)->nullable();
            $table->decimal('depth_m', 4, 2)->nullable();
            $table->string('type')->nullable(); // graded, not
            $table->boolean('has_heating')->default(false);
            $table->boolean('has_barrier')->default(false);
            $table->boolean('has_water_games')->default(false);
            $table->timestamps();
        });

        Schema::create('property_kitchens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->unsignedInteger('dining_chairs')->nullable();
            $table->json('appliances')->nullable();
            $table->timestamps();
        });

        Schema::create('property_bathrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->json('amenities')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_bathrooms');
        Schema::dropIfExists('property_kitchens');
        Schema::dropIfExists('property_pools');
        Schema::dropIfExists('property_living_rooms');
        Schema::dropIfExists('property_bedrooms');
    }
};

