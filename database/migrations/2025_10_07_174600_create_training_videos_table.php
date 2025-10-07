<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('training_videos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('department')->nullable();
            $table->string('duration')->nullable(); // e.g., 15:30
            $table->string('difficulty')->default('beginner'); // beginner|intermediate|advanced
            $table->json('tags')->nullable();
            $table->string('video_url')->nullable(); // external URL
            $table->string('thumbnail_url')->nullable();
            $table->string('video_path')->nullable(); // local storage path
            $table->unsignedBigInteger('views')->default(0);
            $table->unsignedBigInteger('likes')->default(0);
            $table->string('status')->default('active'); // active|inactive|draft
            // Use a nullable indexed column for uploader without FK to avoid cross-engine or schema FK issues
            $table->unsignedBigInteger('uploaded_by')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_videos');
    }
};
