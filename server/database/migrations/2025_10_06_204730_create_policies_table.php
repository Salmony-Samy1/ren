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
        Schema::create('policies', function (Blueprint $table) {
            $table->id(); // Auto-increment primary key
            $table->string('title');
            $table->text('description');
            $table->string('category');
            $table->string('department');
            $table->longText('content');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->json('tags')->nullable();
            $table->date('effective_date')->nullable();
            $table->date('review_date')->nullable();
            $table->string('version', 20)->default('1.0');
            $table->date('last_updated')->nullable();
            $table->enum('status', ['draft', 'active', 'inactive'])->default('draft');
            $table->unsignedInteger('downloads')->default(0);
            $table->string('author')->nullable();
            $table->timestamps();
            
            // Indexes for better query performance
            $table->index('category');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policies');
    }
};
