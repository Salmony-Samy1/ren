<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Update events table
        Schema::table('events', function (Blueprint $table) {
            // Drop foreign key constraint first
            if (Schema::hasColumn('events', 'price_currency_id')) {
                $table->dropConstrainedForeignId('price_currency_id');
            }
            
            // Drop fields that are no longer needed
            if (Schema::hasColumn('events', 'price_per_person')) {
                $table->dropColumn('price_per_person');
            }
        });

        // Update services table
        Schema::table('services', function (Blueprint $table) {
            // Make place_id nullable (it might already be nullable)
            if (Schema::hasColumn('services', 'place_id')) {
                $table->string('place_id')->nullable()->change();
            }
            
            // Add price_currency_id if it doesn't exist (needed for auto-assignment from country_id)
            if (!Schema::hasColumn('services', 'price_currency_id')) {
                $table->foreignId('price_currency_id')->nullable()->after('country_id')->constrained('currencies')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        // Add back the dropped fields for events
        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'price_per_person')) {
                $table->decimal('price_per_person', 12, 2)->nullable()->after('max_individuals');
            }
            if (!Schema::hasColumn('events', 'price_currency_id')) {
                $table->foreignId('price_currency_id')->nullable()->after('price_per_person')->constrained('currencies')->nullOnDelete();
            }
        });

        // Change place_id back to required if needed
        Schema::table('services', function (Blueprint $table) {
            $table->string('place_id')->nullable(false)->change();
        });
    }
};