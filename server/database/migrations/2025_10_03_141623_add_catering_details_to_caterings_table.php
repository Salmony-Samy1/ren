<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('caterings', function (Blueprint $table) {
            $table->string('catering_name')->nullable()->after('service_id');
            $table->string('cuisine_type')->nullable()->after('catering_name');
            $table->decimal('min_order_amount', 10, 2)->nullable()->after('cuisine_type');
            $table->decimal('max_order_amount', 10, 2)->nullable()->after('min_order_amount');
            $table->integer('preparation_time')->nullable()->after('max_order_amount');
            $table->boolean('delivery_available')->default(false)->after('preparation_time');
            $table->integer('delivery_radius_km')->nullable()->after('delivery_available');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('caterings', function (Blueprint $table) {
            $table->dropColumn([
                'catering_name',
                'cuisine_type', 
                'min_order_amount',
                'max_order_amount',
                'preparation_time',
                'delivery_available',
                'delivery_radius_km'
            ]);
        });
    }
};