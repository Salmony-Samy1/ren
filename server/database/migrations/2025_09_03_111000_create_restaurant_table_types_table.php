<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_table_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->string('name'); // e.g., VIP A, VIP Terrace
            $table->text('specs')->nullable(); // description/specs
            $table->integer('capacity_people'); // people per table
            $table->decimal('price_per_person', 12, 2); // vip person price
            $table->integer('count'); // how many tables of this type
            $table->softDeletes();
            $table->timestamps();
            $table->index(['restaurant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_table_types');
    }
};

