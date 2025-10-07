<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['open','in_progress','blocked','done','cancelled'])->default('open')->index();
            $table->enum('priority', ['low','normal','high','urgent'])->default('normal');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['assigned_to','status']);
        });

        Schema::create('team_task_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('team_tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('comment');
            $table->timestamps();
            $table->index(['task_id','created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_task_comments');
        Schema::dropIfExists('team_tasks');
    }
};

