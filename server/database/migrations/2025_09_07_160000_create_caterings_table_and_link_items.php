<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('caterings')) {
            Schema::create('caterings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
                $table->text('description')->nullable();
                $table->json('images')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        // Add catering_id to catering_items
        if (!Schema::hasColumn('catering_items', 'catering_id')) {
            Schema::table('catering_items', function (Blueprint $table) {
                $table->foreignId('catering_id')->nullable()->after('service_id')
                    ->constrained('caterings')->nullOnDelete();
            });
        }

        // Backfill: for each distinct service_id in catering_items, create a catering head and link items
        if (Schema::hasTable('catering_items')) {
            $serviceIds = DB::table('catering_items')
                ->select('service_id')
                ->whereNotNull('service_id')
                ->distinct()
                ->pluck('service_id');
            foreach ($serviceIds as $sid) {
                $exists = DB::table('caterings')->where('service_id', $sid)->value('id');
                if (!$exists) {
                    $cateringId = DB::table('caterings')->insertGetId([
                        'service_id' => $sid,
                        'description' => null,
                        'images' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $cateringId = $exists;
                }
                DB::table('catering_items')->where('service_id', $sid)->update(['catering_id' => $cateringId]);
            }
        }
    }

    public function down(): void
    {
        // Remove catering_id from items
        if (Schema::hasColumn('catering_items', 'catering_id')) {
            Schema::table('catering_items', function (Blueprint $table) {
                $table->dropConstrainedForeignId('catering_id');
            });
        }
        Schema::dropIfExists('caterings');
    }
};

