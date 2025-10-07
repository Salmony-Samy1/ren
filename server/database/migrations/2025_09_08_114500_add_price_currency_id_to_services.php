<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'price_currency_id')) {
                $table->foreignId('price_currency_id')->nullable()->after('price_currency')->constrained('currencies')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'price_currency_id')) {
                $table->dropConstrainedForeignId('price_currency_id');
            }
        });
    }
};

