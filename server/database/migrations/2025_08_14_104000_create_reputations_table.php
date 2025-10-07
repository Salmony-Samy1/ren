<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reputations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('subject_id');
            $table->string('subject_type');
            $table->string('name');
            $table->integer('point');
            $table->unsignedBigInteger('payee_id')->nullable();
            $table->string('payee_type')->nullable();
            $table->timestamps();

            $table->index(['subject_id', 'subject_type']);
            $table->index(['payee_id', 'payee_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reputations');
    }
};