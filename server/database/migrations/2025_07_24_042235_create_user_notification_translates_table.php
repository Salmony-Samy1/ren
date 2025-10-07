<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_notification_translates', function (Blueprint $table) {
            $table->id();
            $table->string('locale');
            $table->foreignId('user_notification_id')->constrained('user_notifications');
            $table->longText('message');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notification_translates');
    }
};
