<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('table_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_table_id')->constrained('restaurant_tables')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->enum('status', ['confirmed','tentative','cancelled'])->default('tentative')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['restaurant_table_id','start_time','end_time'], 'tr_table_time_idx');
            $table->index(['restaurant_table_id','status','start_time'], 'tr_table_status_start_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_reservations');
    }
};

