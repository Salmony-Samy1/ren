<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CompanyProfile;
use App\Models\User;
use Carbon\Carbon;

class NationalIdVerificationStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'national-id:stats {--days=30 : عدد الأيام للإحصائيات}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'عرض إحصائيات التحقق من الهوية الوطنية';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $this->info("إحصائيات التحقق من الهوية الوطنية - آخر {$days} يوم");
        $this->info("=" . str_repeat("=", 50));

        // إحصائيات عامة
        $totalProviders = CompanyProfile::count();
        $verifiedProviders = CompanyProfile::whereNotNull('national_id_verified_at')->count();
        $pendingVerification = CompanyProfile::whereNotNull('national_id')->whereNull('national_id_verified_at')->count();
        $failedVerification = CompanyProfile::whereNotNull('national_id_verification_failed_at')->count();
        $noNationalId = CompanyProfile::whereNull('national_id')->count();

        $this->info("📊 الإحصائيات العامة:");
        $this->table(
            ['المقياس', 'العدد', 'النسبة'],
            [
                ['إجمالي مزودي الخدمة', $totalProviders, '100%'],
                ['تم التحقق منهم', $verifiedProviders, round(($verifiedProviders / $totalProviders) * 100, 1) . '%'],
                ['في انتظار التحقق', $pendingVerification, round(($pendingVerification / $totalProviders) * 100, 1) . '%'],
                ['فشل في التحقق', $failedVerification, round(($failedVerification / $totalProviders) * 100, 1) . '%'],
                ['بدون هوية وطنية', $noNationalId, round(($noNationalId / $totalProviders) * 100, 1) . '%'],
            ]
        );

        // إحصائيات حسب الفترة الزمنية
        $startDate = Carbon::now()->subDays($days);
        
        $recentVerifications = CompanyProfile::whereNotNull('national_id_verified_at')
            ->where('national_id_verified_at', '>=', $startDate)
            ->count();

        $recentFailures = CompanyProfile::whereNotNull('national_id_verification_failed_at')
            ->where('national_id_verification_failed_at', '>=', $startDate)
            ->count();

        $recentRegistrations = CompanyProfile::where('created_at', '>=', $startDate)->count();

        $this->info("\n📅 إحصائيات آخر {$days} يوم:");
        $this->table(
            ['المقياس', 'العدد'],
            [
                ['تحققات ناجحة', $recentVerifications],
                ['تحققات فاشلة', $recentFailures],
                ['تسجيلات جديدة', $recentRegistrations],
            ]
        );

        // إحصائيات حسب البوابة (إذا كانت متوفرة)
        $this->info("\n🔍 إحصائيات حسب البوابة:");
        $gatewayStats = CompanyProfile::whereNotNull('national_id_verification_data')
            ->get()
            ->groupBy(function ($profile) {
                $data = $profile->national_id_verification_data;
                return $data['gateway'] ?? 'unknown';
            })
            ->map(function ($profiles) {
                return $profiles->count();
            });

        if ($gatewayStats->isNotEmpty()) {
            $gatewayTable = [];
            foreach ($gatewayStats as $gateway => $count) {
                $gatewayTable[] = [$gateway, $count, round(($count / $totalProviders) * 100, 1) . '%'];
            }
            
            $this->table(
                ['البوابة', 'العدد', 'النسبة'],
                $gatewayTable
            );
        } else {
            $this->warn("لا توجد بيانات عن البوابات المستخدمة.");
        }

        // تحليل الأخطاء
        $this->info("\n❌ تحليل الأخطاء:");
        $errorStats = CompanyProfile::whereNotNull('national_id_verification_failed_at')
            ->whereNotNull('national_id_verification_data')
            ->get()
            ->groupBy(function ($profile) {
                $data = $profile->national_id_verification_data;
                return $data['error_code'] ?? 'unknown';
            })
            ->map(function ($profiles) {
                return $profiles->count();
            })
            ->sortDesc();

        if ($errorStats->isNotEmpty()) {
            $errorTable = [];
            foreach ($errorStats as $errorCode => $count) {
                $errorTable[] = [$errorCode, $count];
            }
            
            $this->table(
                ['رمز الخطأ', 'العدد'],
                $errorTable
            );
        } else {
            $this->info("لا توجد أخطاء مسجلة.");
        }

        // توصيات
        $this->info("\n💡 التوصيات:");
        
        if ($pendingVerification > 0) {
            $this->warn("- يوجد {$pendingVerification} مزود خدمة في انتظار التحقق من الهوية الوطنية");
        }
        
        if ($failedVerification > 0) {
            $this->warn("- يوجد {$failedVerification} مزود خدمة فشل في التحقق من الهوية الوطنية");
        }
        
        if ($noNationalId > 0) {
            $this->warn("- يوجد {$noNationalId} مزود خدمة بدون هوية وطنية");
        }
        
        if ($verifiedProviders > 0) {
            $this->info("✅ تم التحقق من {$verifiedProviders} مزود خدمة بنجاح");
        }

        $this->info("\n" . str_repeat("=", 50));
        $this->info("تم إنشاء التقرير بنجاح!");

        return 0;
    }
}
