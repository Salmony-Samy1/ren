<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->foreignId('region_id')->constrained('regions')->onDelete('cascade');
            $table->foreignId('neigbourhood_id')->constrained('neigbourhoods')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
            $table->dropForeign(['neigbourhood_id']);
        });
    }
};
