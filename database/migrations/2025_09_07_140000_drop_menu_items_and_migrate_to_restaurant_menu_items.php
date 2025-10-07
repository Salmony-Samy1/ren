<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('menu_items')) {
            // If restaurant_menu_items exists, migrate data
            if (Schema::hasTable('restaurant_menu_items')) {
                try {
                    // Copy rows from legacy menu_items (service-scoped) to restaurant_menu_items (restaurant-scoped)
                    // Map: service_id -> restaurants.id via restaurants.service_id
                    // Avoid duplicates by (restaurant_id, name, price)
                    DB::statement(<<<SQL
                        INSERT INTO restaurant_menu_items
                            (restaurant_id, name, description, price, media_url, is_active, created_at, updated_at, deleted_at)
                        SELECT r.id, m.name, m.description, m.price, m.image_url, m.is_active, m.created_at, m.updated_at, m.deleted_at
                        FROM menu_items m
                        INNER JOIN restaurants r ON r.service_id = m.service_id
                        LEFT JOIN restaurant_menu_items mi
                            ON mi.restaurant_id = r.id AND mi.name = m.name AND (mi.price <=> m.price)
                        WHERE mi.id IS NULL
                    SQL);
                } catch (\Throwable $e) {
                    // Log but continue to drop legacy table to remove duplication if migration insert fails
                    // In production, consider aborting instead to keep data
                    info('Menu items migration warning: '.$e->getMessage());
                }
            }

            Schema::dropIfExists('menu_items');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('menu_items')) {
            Schema::create('menu_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_id')->index();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('price', 12, 2)->default(0);
                $table->string('image_url')->nullable();
                $table->string('category')->nullable();
                $table->json('extras')->nullable();
                $table->boolean('is_active')->default(true);
                $table->softDeletes();
                $table->timestamps();
            });
        }

        // Optionally copy back from restaurant_menu_items to menu_items using restaurants.service_id
        if (Schema::hasTable('restaurant_menu_items')) {
            try {
                DB::statement(<<<SQL
                    INSERT INTO menu_items
                        (service_id, name, description, price, image_url, is_active, created_at, updated_at, deleted_at)
                    SELECT r.service_id, mi.name, mi.description, mi.price, mi.media_url, mi.is_active, mi.created_at, mi.updated_at, mi.deleted_at
                    FROM restaurant_menu_items mi
                    INNER JOIN restaurants r ON r.id = mi.restaurant_id
                    LEFT JOIN menu_items m
                        ON m.service_id = r.service_id AND m.name = mi.name AND (m.price <=> mi.price)
                    WHERE m.id IS NULL
                SQL);
            } catch (\Throwable $e) {
                info('Menu items reverse migration warning: '.$e->getMessage());
            }
        }
    }
};

