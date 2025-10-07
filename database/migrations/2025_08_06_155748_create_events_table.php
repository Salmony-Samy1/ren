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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->string('event_name');
            $table->text('description');
            $table->string('images')->nullable();
            $table->integer('max_individuals');
            $table->enum('gender_type', ['male', 'female', 'both']);
            $table->boolean('hospitality_available')->default(false);
            $table->string('pricing_type');
            $table->decimal('base_price', 10, 2);
            $table->decimal('discount_price', 10, 2)->nullable();
            $table->json('prices_by_age')->nullable();
            $table->string('cancellation_policy');
            $table->string('meeting_point');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
