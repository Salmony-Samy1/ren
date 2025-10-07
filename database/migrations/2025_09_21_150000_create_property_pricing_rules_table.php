<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->string('name')->nullable();
            // Either a fixed price override or a modifier
            $table->enum('type', ['fixed', 'percent'])->default('fixed');
            $table->decimal('amount', 10, 2)->default(0);
            // Optional applicability
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->unsignedTinyInteger('day_of_week')->nullable(); // 0-6 Sun-Sat
            $table->unsignedInteger('min_stay_nights')->nullable();
            $table->unsignedInteger('max_stay_nights')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['property_id','date_from','date_to']);
            $table->index(['property_id','day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_pricing_rules');
    }
};

