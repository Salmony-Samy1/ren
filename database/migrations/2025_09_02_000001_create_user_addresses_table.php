<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('label')->nullable(); // Home, Work, etc.
            $table->string('name')->nullable(); // Recipient name
            $table->string('phone')->nullable();
            $table->string('country_code', 255)->nullable();
            $table->string('address'); // formatted address
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('place_id')->nullable();
            $table->text('notes')->nullable(); // delivery instructions
            $table->string('type')->nullable(); // shipping, billing (optional)
            $table->boolean('is_default')->default(false);
            $table->softDeletes();
            $table->timestamps();
            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};

