<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->string('name')->nullable(); // Optional label
            $table->enum('type', ['Normal','VIP'])->default('Normal');
            $table->integer('capacity_people')->default(2);
            $table->decimal('price_per_person', 12, 2)->nullable(); // used for Normal
            $table->decimal('price_per_table', 12, 2)->nullable(); // used for VIP
            $table->integer('quantity')->default(1);
            $table->enum('re_availability_type', ['AUTO','MANUAL'])->default('AUTO');
            $table->integer('auto_re_availability_minutes')->nullable();
            $table->json('conditions')->nullable(); // booking conditions per table type
            $table->json('amenities')->nullable(); // table amenities
            $table->json('media')->nullable(); // optional images/videos
            $table->softDeletes();
            $table->timestamps();
            $table->index(['restaurant_id','type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_tables');
    }
};

