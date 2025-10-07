<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Ensure target tables exist (created by 2025_09_05_100000_create_hobbies_and_pivots.php)
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

        // Migrate legacy customer_hobbies (name-based) into normalized pivots
        if (Schema::hasTable('customer_hobbies')) {
            // Process in chunks to avoid memory spikes
            DB::table('customer_hobbies')->orderBy('id')->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    $name = trim((string)($row->name ?? ''));
                    $customerProfileId = (int)($row->customer_profile_id ?? 0);
                    if ($name === '' || $customerProfileId <= 0) {
                        continue;
                    }

                    // Get or create hobby id by name
                    $hobby = \App\Models\Hobby::withTrashed()->firstOrCreate(['name' => $name], ['deleted_at' => null]);

                    // Upsert into pivot
                    DB::table('customer_profile_hobby')->updateOrInsert(
                        ['customer_profile_id' => $customerProfileId, 'hobby_id' => $hobby->id],
                        ['updated_at' => now(), 'created_at' => DB::raw('COALESCE(created_at, NOW())')]
                    );
                }
            });

            // Drop legacy table after migration
            Schema::dropIfExists('customer_hobbies');
        }
    }

    public function down(): void
    {
        // Recreate legacy table if needed and copy data back from pivots (best-effort)
        if (!Schema::hasTable('customer_hobbies')) {
            Schema::create('customer_hobbies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_profile_id')->constrained('customer_profiles')->cascadeOnDelete();
                $table->string('name');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Copy back using existing pivots and hobbies
        if (Schema::hasTable('customer_profile_hobby') && Schema::hasTable('hobbies')) {
            $pairs = DB::table('customer_profile_hobby as cph')
                ->join('hobbies as h', 'h.id', '=', 'cph.hobby_id')
                ->select('cph.customer_profile_id', 'h.name')
                ->get();
            foreach ($pairs as $p) {
                DB::table('customer_hobbies')->updateOrInsert(
                    ['customer_profile_id' => $p->customer_profile_id, 'name' => $p->name],
                    ['updated_at' => now(), 'created_at' => now()]
                );
            }
        }
    }
};

