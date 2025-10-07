<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('neigbourhood_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('neigbourhood_id')->constrained('neigbourhoods')->cascadeOnDelete();
            $table->string('locale')->index();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('neigbourhood_translations');
    }
};
