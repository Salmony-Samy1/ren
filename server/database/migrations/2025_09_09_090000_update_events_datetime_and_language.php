<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'start_at')) {
                $table->dateTime('start_at')->nullable()->after('start_date');
            }
            if (!Schema::hasColumn('events', 'end_at')) {
                $table->dateTime('end_at')->nullable()->after('end_date');
            }
            if (!Schema::hasColumn('events', 'language')) {
                $table->string('language', 10)->nullable()->after('description'); // ar|en|both
            }
            if (!Schema::hasColumn('events', 'max_individuals')) {
                $table->unsignedInteger('max_individuals')->nullable()->after('language');
            }
            if (!Schema::hasColumn('events', 'price_per_person')) {
                $table->decimal('price_per_person', 12, 2)->nullable()->after('max_individuals');
            }
            if (!Schema::hasColumn('events', 'price_currency_id')) {
                $table->foreignId('price_currency_id')->nullable()->after('price_per_person')->constrained('currencies')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'price_currency_id')) { $table->dropConstrainedForeignId('price_currency_id'); }
            if (Schema::hasColumn('events', 'price_per_person')) { $table->dropColumn('price_per_person'); }
            if (Schema::hasColumn('events', 'max_individuals')) { $table->dropColumn('max_individuals'); }
            if (Schema::hasColumn('events', 'language')) { $table->dropColumn('language'); }
            if (Schema::hasColumn('events', 'end_at')) { $table->dropColumn('end_at'); }
            if (Schema::hasColumn('events', 'start_at')) { $table->dropColumn('start_at'); }
        });
    }
};

