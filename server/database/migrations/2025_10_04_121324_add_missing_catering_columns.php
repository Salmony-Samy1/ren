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
            $table->boolean('pickup_available')->default(false)->after('delivery_available');
            $table->boolean('on_site_available')->default(false)->after('pickup_available');
            $table->text('cancellation_policy')->nullable()->after('on_site_available');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('caterings', function (Blueprint $table) {
            $table->dropColumn(['pickup_available', 'on_site_available', 'cancellation_policy']);
        });
    }
};
