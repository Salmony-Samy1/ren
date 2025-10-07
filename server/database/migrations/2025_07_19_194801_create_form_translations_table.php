<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('form_translations', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->longText('help_text')->nullable();
            $table->string('locale')->index();
            $table->foreignId('form_id')->constrained('forms')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_translations');
    }
};
