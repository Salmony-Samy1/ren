<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('neigbourhoods', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active');
            $table->foreignId('region_id')->constrained('regions')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('neigbourhoods');
    }
};
