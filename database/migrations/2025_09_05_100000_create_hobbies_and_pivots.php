<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('hobbies')) {
            Schema::create('hobbies', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('customer_profile_hobby')) {
            Schema::create('customer_profile_hobby', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_profile_id')->constrained('customer_profiles')->cascadeOnDelete();
                $table->foreignId('hobby_id')->constrained('hobbies')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['customer_profile_id','hobby_id']);
            });
        }

        if (!Schema::hasTable('company_profile_hobby')) {
            Schema::create('company_profile_hobby', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_profile_id')->constrained('company_profiles')->cascadeOnDelete();
                $table->foreignId('hobby_id')->constrained('hobbies')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['company_profile_id','hobby_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('company_profile_hobby');
        Schema::dropIfExists('customer_profile_hobby');
        Schema::dropIfExists('hobbies');
    }
};

