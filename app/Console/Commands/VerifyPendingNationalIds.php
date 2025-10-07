<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CompanyProfile;
use App\Jobs\VerifyNationalIdJob;

class VerifyPendingNationalIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'national-id:verify-pending {--limit=10 : عدد الملفات الشخصية للتحقق منها} {--force : إجبار التحقق حتى لو تم التحقق منه سابقاً}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'التحقق من الهويات الوطنية المعلقة';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = (int) $this->option('limit');
        $force = $this->option('force');

        $this->info("بدء التحقق من الهويات الوطنية المعلقة...");
        $this->info("الحد الأقصى: {$limit} ملف شخصي");

        // البحث عن الملفات الشخصية التي تحتاج للتحقق
        $query = CompanyProfile::whereNotNull('national_id');

        if (!$force) {
            $query->whereNull('national_id_verified_at');
        }

        $profiles = $query->limit($limit)->get();

        if ($profiles->isEmpty()) {
            $this->info("لا توجد ملفات شخصية تحتاج للتحقق من الهوية الوطنية.");
            return 0;
        }

        $this->info("تم العثور على {$profiles->count()} ملف شخصي للتحقق.");

        $bar = $this->output->createProgressBar($profiles->count());
        $bar->start();

        $successCount = 0;
        $failedCount = 0;

        foreach ($profiles as $profile) {
            try {
                // إرسال Job للتحقق
                VerifyNationalIdJob::dispatch($profile->id);
                $successCount++;
            } catch (\Exception $e) {
                $this->error("فشل في إرسال job للتحقق من الملف الشخصي {$profile->id}: " . $e->getMessage());
                $failedCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("تم إرسال {$successCount} job للتحقق بنجاح.");
        
        if ($failedCount > 0) {
            $this->warn("فشل في إرسال {$failedCount} job للتحقق.");
        }

        $this->info("سيتم معالجة التحقق في الخلفية.");

        return 0;
    }
}
