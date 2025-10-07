<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('quality_reviews')) {
            Schema::create('quality_reviews', function (Blueprint $table) {
                $table->id();
                $table->morphs('reviewable'); // service, provider, booking, etc. (auto indexed)
                $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedTinyInteger('score')->comment('0-100');
                $table->string('kpi')->nullable(); // which KPI was targeted
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['kpi','created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_reviews');
    }
};

