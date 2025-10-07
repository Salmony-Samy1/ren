<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('base_currency', 8); // e.g., SAR
            $table->string('quote_currency', 8); // e.g., BHD
            $table->decimal('rate', 18, 8); // 1 base = rate quote
            $table->timestamps();
            $table->unique(['base_currency', 'quote_currency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};

