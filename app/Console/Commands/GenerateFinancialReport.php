<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FinancialReportService;
use Carbon\Carbon;

class GenerateFinancialReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'financial:report 
                            {type : نوع التقرير (revenue, monthly, daily, revenue-by-service-type, provider, commission, tax, discount, performance, trends, provider-profitability)}
                            {--period= : الفترة الزمنية (today, yesterday, this_week, this_month, this_year, last_7_days, last_30_days, last_90_days)}
                            {--start-date= : تاريخ البداية (Y-m-d)}
                            {--end-date= : تاريخ النهاية (Y-m-d)}
                            {--year= : السنة للتقرير الشهري}
                            {--user-id= : معرف المستخدم}
                            {--user-type= : نوع المستخدم (customer, provider)}
                            {--export= : تصدير إلى ملف (csv, json)}
                            {--output= : مسار ملف الإخراج}
                            {--min-revenue= : الحد الأدنى للإيرادات}
                            {--min-invoices= : الحد الأدنى لعدد الفواتير}
                            {--sort-by= : ترتيب حسب (revenue, profitability_score, invoices_count, average_invoice_value)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'توليد التقارير المالية';

    protected $financialReportService;

    public function __construct(FinancialReportService $financialReportService)
    {
        parent::__construct();
        $this->financialReportService = $financialReportService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument('type');
        $period = $this->option('period');
        $startDate = $this->option('start-date');
        $endDate = $this->option('end-date');
        $year = $this->option('year');
        $userId = $this->option('user-id');
        $userType = $this->option('user-type');
        $export = $this->option('export');
        $output = $this->option('output');

        $this->info("🎯 بدء توليد التقرير المالي: {$type}");
        $this->info("=" . str_repeat("=", 50));

        // تجميع الفلاتر
        $filters = [];
        if ($period) $filters['period'] = $period;
        if ($startDate) $filters['start_date'] = $startDate;
        if ($endDate) $filters['end_date'] = $endDate;
        if ($userId) $filters['user_id'] = $userId;
        if ($userType) $filters['user_type'] = $userType;

        try {
            $report = $this->generateReport($type, $filters, $year);
            
            if ($export) {
                $this->exportReport($report, $export, $output, $type);
            } else {
                $this->displayReport($report, $type);
            }

            $this->info("✅ تم توليد التقرير بنجاح!");
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ فشل في توليد التقرير: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * توليد التقرير حسب النوع
     */
    protected function generateReport(string $type, array $filters, ?int $year = null): array
    {
        switch ($type) {
            case 'revenue':
                return $this->financialReportService->getRevenueReport($filters);
            
            case 'monthly':
                return $this->financialReportService->getMonthlyRevenueReport($year);
            
            case 'daily':
                $startDate = $filters['start_date'] ?? now()->subDays(30)->format('Y-m-d');
                $endDate = $filters['end_date'] ?? now()->format('Y-m-d');
                return $this->financialReportService->getDailyRevenueReport($startDate, $endDate);
            
            case 'revenue-by-service-type':
                return $this->financialReportService->getRevenueByServiceType($filters);
            
            case 'provider':
                return $this->financialReportService->getRevenueByProvider($filters);
            
            case 'commission':
                return $this->financialReportService->getCommissionReport($filters);
            
            case 'tax':
                return $this->financialReportService->getTaxReport($filters);
            
            case 'discount':
                return $this->financialReportService->getDiscountReport($filters);
            
            case 'performance':
                return $this->financialReportService->getFinancialPerformanceReport($filters);
            
            case 'trends':
                return $this->financialReportService->getTrendAnalysis($filters);
            
            case 'provider-profitability':
                return $this->financialReportService->getProviderProfitabilityReport($filters);
            
            default:
                throw new \InvalidArgumentException("نوع التقرير غير مدعوم: {$type}");
        }
    }

    /**
     * عرض التقرير في Terminal
     */
    protected function displayReport(array $report, string $type): void
    {
        $this->info("📊 نتائج التقرير:");
        $this->newLine();

        switch ($type) {
            case 'revenue':
                $this->displayRevenueReport($report);
                break;
            
            case 'monthly':
                $this->displayMonthlyReport($report);
                break;
            
            case 'daily':
                $this->displayDailyReport($report);
                break;
            
            case 'revenue-by-service-type':
                $this->displayServiceTypeReport($report);
                break;
            
            case 'provider':
                $this->displayProviderReport($report);
                break;
            
            case 'commission':
                $this->displayCommissionReport($report);
                break;
            
            case 'tax':
                $this->displayTaxReport($report);
                break;
            
            case 'discount':
                $this->displayDiscountReport($report);
                break;
            
            case 'performance':
                $this->displayPerformanceReport($report);
                break;
            
            case 'trends':
                $this->displayTrendsReport($report);
                break;
            
            case 'provider-profitability':
                $this->displayProviderProfitabilityReport($report);
                break;
        }
    }

    /**
     * عرض تقرير الإيرادات
     */
    protected function displayRevenueReport(array $report): void
    {
        $this->table(
            ['المقياس', 'القيمة'],
            [
                ['إجمالي الإيرادات', number_format($report['total_revenue'], 2) . ' ريال'],
                ['إيرادات المنصة', number_format($report['total_platform_amount'], 2) . ' ريال'],
                ['إيرادات المزودين', number_format($report['total_provider_amount'], 2) . ' ريال'],
                ['إجمالي العمولات', number_format($report['total_commission'], 2) . ' ريال'],
                ['إجمالي الضرائب', number_format($report['total_tax'], 2) . ' ريال'],
                ['إجمالي الخصومات', number_format($report['total_discount'], 2) . ' ريال'],
                ['صافي الإيرادات', number_format($report['net_revenue'], 2) . ' ريال'],
                ['هامش الربح', $report['profit_margin'] . '%'],
                ['عدد الفواتير', $report['total_invoices']]
            ]
        );
    }

    /**
     * عرض التقرير الشهري
     */
    protected function displayMonthlyReport(array $report): void
    {
        $this->info("📅 التقرير الشهري لعام {$report['year']}:");
        $this->newLine();

        $monthlyData = [];
        foreach ($report['months'] as $month => $data) {
            $monthlyData[] = [
                $month,
                number_format($data['revenue'], 2) . ' ريال',
                number_format($data['platform_revenue'], 2) . ' ريال',
                $data['invoices_count']
            ];
        }

        $this->table(
            ['الشهر', 'الإيرادات', 'إيرادات المنصة', 'عدد الفواتير'],
            $monthlyData
        );

        $this->newLine();
        $this->info("📊 الملخص:");
        $this->table(
            ['المقياس', 'القيمة'],
            [
                ['إجمالي الإيرادات', number_format($report['total_revenue'], 2) . ' ريال'],
                ['إجمالي إيرادات المنصة', number_format($report['total_platform_revenue'], 2) . ' ريال'],
                ['إجمالي الفواتير', $report['total_invoices']]
            ]
        );
    }

    /**
     * عرض التقرير اليومي
     */
    protected function displayDailyReport(array $report): void
    {
        $this->info("📅 التقرير اليومي من {$report['start_date']} إلى {$report['end_date']}:");
        $this->newLine();

        $dailyData = [];
        foreach ($report['daily_data'] as $data) {
            $dailyData[] = [
                $data['date'],
                number_format($data['revenue'], 2) . ' ريال',
                number_format($data['platform_revenue'], 2) . ' ريال',
                $data['invoices_count']
            ];
        }

        $this->table(
            ['التاريخ', 'الإيرادات', 'إيرادات المنصة', 'عدد الفواتير'],
            $dailyData
        );

        $this->newLine();
        $this->info("📊 الملخص:");
        $this->table(
            ['المقياس', 'القيمة'],
            [
                ['إجمالي الإيرادات', number_format($report['total_revenue'], 2) . ' ريال'],
                ['إجمالي إيرادات المنصة', number_format($report['total_platform_revenue'], 2) . ' ريال'],
                ['إجمالي الفواتير', $report['total_invoices']],
                ['متوسط الإيرادات اليومية', number_format($report['average_daily_revenue'], 2) . ' ريال']
            ]
        );
    }

    /**
     * عرض تقرير نوع الخدمة
     */
    protected function displayServiceTypeReport(array $report): void
    {
        $this->info("🏷️ تقرير الإيرادات حسب نوع الخدمة:");
        $this->newLine();

        $serviceData = [];
        foreach ($report['service_types'] as $service) {
            $serviceData[] = [
                $service['service_type'],
                number_format($service['total_revenue'], 2) . ' ريال',
                number_format($service['platform_revenue'], 2) . ' ريال',
                $service['revenue_percentage'] . '%',
                $service['invoices_count']
            ];
        }

        $this->table(
            ['نوع الخدمة', 'إجمالي الإيرادات', 'إيرادات المنصة', 'النسبة', 'عدد الفواتير'],
            $serviceData
        );
    }

    /**
     * عرض تقرير المزودين
     */
    protected function displayProviderReport(array $report): void
    {
        $this->info("👥 تقرير الإيرادات حسب المزود:");
        $this->newLine();

        $providerData = [];
        foreach ($report['providers'] as $provider) {
            $providerData[] = [
                $provider['provider_name'],
                number_format($provider['total_revenue'], 2) . ' ريال',
                number_format($provider['platform_revenue'], 2) . ' ريال',
                $provider['revenue_percentage'] . '%',
                $provider['invoices_count']
            ];
        }

        $this->table(
            ['اسم المزود', 'إجمالي الإيرادات', 'إيرادات المنصة', 'النسبة', 'عدد الفواتير'],
            $providerData
        );
    }

    /**
     * عرض تقرير العمولات
     */
    protected function displayCommissionReport(array $report): void
    {
        $this->info("💰 تقرير العمولات:");
        $this->newLine();

        $this->table(
            ['المقياس', 'القيمة'],
            [
                ['إجمالي العمولات', number_format($report['summary']['total_commission'], 2) . ' ريال'],
                ['إجمالي إيرادات المنصة', number_format($report['summary']['total_platform_amount'], 2) . ' ريال'],
                ['متوسط العمولة', number_format($report['summary']['average_commission'], 2) . ' ريال'],
                ['عدد الفواتير', $report['summary']['total_invoices']],
                ['فواتير العمولات', $report['summary']['commission_invoices']],
                ['نسبة العمولات', $report['summary']['commission_rate'] . '%']
            ]
        );

        if (!empty($report['by_service_type'])) {
            $this->newLine();
            $this->info("🏷️ العمولات حسب نوع الخدمة:");
            
            $serviceData = [];
            foreach ($report['by_service_type'] as $service) {
                $serviceData[] = [
                    $service['service_type'],
                    number_format($service['total_commission'], 2) . ' ريال',
                    number_format($service['average_commission'], 2) . ' ريال',
                    $service['invoices_count']
                ];
            }

            $this->table(
                ['نوع الخدمة', 'إجمالي العمولات', 'متوسط العمولة', 'عدد الفواتير'],
                $serviceData
            );
        }
    }

    /**
     * عرض تقرير الضرائب
     */
    protected function displayTaxReport(array $report): void
    {
        $this->info("🏛️ تقرير الضرائب:");
        $this->newLine();

        $this->table(
            ['المقياس', 'القيمة'],
            [
                ['إجمالي الضرائب', number_format($report['summary']['total_tax'], 2) . ' ريال'],
                ['متوسط الضريبة', number_format($report['summary']['average_tax'], 2) . ' ريال'],
                ['عدد الفواتير', $report['summary']['total_invoices']],
                ['الفواتير الخاضعة للضريبة', $report['summary']['taxable_invoices']],
                ['نسبة الضرائب', $report['summary']['tax_rate'] . '%']
            ]
        );
    }

    /**
     * عرض تقرير الخصومات
     */
    protected function displayDiscountReport(array $report): void
    {
        $this->info("🎫 تقرير الخصومات:");
        $this->newLine();

        $this->table(
            ['المقياس', 'القيمة'],
            [
                ['إجمالي الخصومات', number_format($report['summary']['total_discount'], 2) . ' ريال'],
                ['متوسط الخصم', number_format($report['summary']['average_discount'], 2) . ' ريال'],
                ['عدد الفواتير', $report['summary']['total_invoices']],
                ['الفواتير المخصومة', $report['summary']['discounted_invoices']],
                ['نسبة الخصومات', $report['summary']['discount_rate'] . '%']
            ]
        );
    }

    /**
     * عرض تقرير الأداء
     */
    protected function displayPerformanceReport(array $report): void
    {
        $this->info("💰 تقرير الأداء المالي:");
        $this->newLine();

        $this->info("💰 ملخص الإيرادات:");
        $this->table(
            ['المقياس', 'القيمة'],
            [
                ['إجمالي الإيرادات', number_format($report['revenue_summary']['total_revenue'], 2) . ' ريال'],
                ['صافي الإيرادات', number_format($report['revenue_summary']['net_revenue'], 2) . ' ريال'],
                ['إيرادات المنصة', number_format($report['revenue_summary']['total_platform_amount'], 2) . ' ريال']
            ]
        );

        $this->newLine();
        $this->info("📊 مؤشرات الأداء:");
        $this->table(
            ['المقياس', 'القيمة'],
            [
                ['هامش الربح الإجمالي', $report['performance_metrics']['gross_profit_margin'] . '%'],
                ['هامش الربح الصافي', $report['performance_metrics']['net_profit_margin'] . '%'],
                ['نسبة التكاليف', $report['performance_metrics']['cost_ratio'] . '%'],
                ['متوسط الإيرادات لكل فاتورة', number_format($report['performance_metrics']['revenue_per_invoice'], 2) . ' ريال'],
                ['متوسط إيرادات المنصة لكل فاتورة', number_format($report['performance_metrics']['platform_revenue_per_invoice'], 2) . ' ريال']
            ]
        );
    }

    /**
     * عرض تقرير التحليلات الاستراتيجية
     */
    protected function displayTrendsReport(array $report): void
    {
        $this->info("📈 تقرير التحليلات الاستراتيجية:");
        $this->newLine();

        $this->info("📅 الفترة: {$report['period']['start_date']} إلى {$report['period']['end_date']}");
        $this->newLine();

        if (!empty($report['trends'])) {
            $this->info("📊 اتجاهات النمو الشهرية:");
            $trendsData = [];
            foreach ($report['trends'] as $trend) {
                $trendsData[] = [
                    $trend['month'],
                    number_format($trend['revenue'], 2) . ' ريال',
                    $trend['revenue_change'] . '%',
                    number_format($trend['platform_revenue'], 2) . ' ريال',
                    $trend['platform_revenue_change'] . '%',
                    $trend['invoices_count']
                ];
            }
            
            $this->table(
                ['الشهر', 'الإيرادات', 'نمو الإيرادات', 'إيرادات المنصة', 'نمو المنصة', 'عدد الفواتير'],
                $trendsData
            );
        }

        if (!empty($report['average_growth'])) {
            $this->newLine();
            $this->info("📈 متوسط النمو:");
            $this->table(
                ['المقياس', 'متوسط النمو'],
                [
                    ['الإيرادات', $report['average_growth']['revenue'] . '%'],
                    ['إيرادات المنصة', $report['average_growth']['platform_revenue'] . '%'],
                    ['العمولات', $report['average_growth']['commission'] . '%'],
                    ['عدد الفواتير', $report['average_growth']['invoices_count'] . '%']
                ]
            );
        }

        if (!empty($report['forecast'])) {
            $this->newLine();
            $this->info("🔮 التوقعات المستقبلية (3 أشهر):");
            $forecastData = [];
            foreach ($report['forecast'] as $forecast) {
                $forecastData[] = [
                    $forecast['month'],
                    number_format($forecast['revenue'], 2) . ' ريال',
                    number_format($forecast['platform_revenue'], 2) . ' ريال',
                    number_format($forecast['commission'], 2) . ' ريال',
                    $forecast['invoices_count']
                ];
            }
            
            $this->table(
                ['الشهر', 'الإيرادات المتوقعة', 'إيرادات المنصة المتوقعة', 'العمولات المتوقعة', 'عدد الفواتير المتوقع'],
                $forecastData
            );
        }
    }

    /**
     * عرض تقرير الربحية المزودين
     */
    protected function displayProviderProfitabilityReport(array $report): void
    {
        $this->info("👥 تقرير الربحية حسب المزود:");
        $this->newLine();

        if (!empty($report['providers'])) {
            $this->info("📊 المزودين:");
            $providerData = [];
            foreach ($report['providers'] as $provider) {
                $providerData[] = [
                    $provider['provider_name'],
                    number_format($provider['total_revenue'], 2) . ' ريال',
                    number_format($provider['platform_revenue'], 2) . ' ريال',
                    $provider['invoices_count'],
                    number_format($provider['average_invoice_value'], 2) . ' ريال',
                    $provider['commission_rate'] . '%',
                    $provider['profitability_score'] . '/100'
                ];
            }
            
            $this->table(
                ['اسم المزود', 'إجمالي الإيرادات', 'إيرادات المنصة', 'عدد الفواتير', 'متوسط الفاتورة', 'معدل العمولة', 'درجة الربحية'],
                $providerData
            );
        }

        if (!empty($report['summary'])) {
            $this->newLine();
            $this->info("📊 الملخص:");
            $this->table(
                ['المقياس', 'القيمة'],
                [
                    ['إجمالي الإيرادات', number_format($report['summary']['total_revenue'], 2) . ' ريال'],
                    ['إجمالي إيرادات المنصة', number_format($report['summary']['total_platform_revenue'], 2) . ' ريال'],
                    ['إجمالي الفواتير', $report['summary']['total_invoices']],
                    ['عدد المزودين', $report['summary']['providers_count']],
                    ['متوسط إيرادات المزود', number_format($report['summary']['average_provider_revenue'], 2) . ' ريال']
                ]
            );
        }
    }

    /**
     * تصدير التقرير
     */
    protected function exportReport(array $report, string $export, ?string $output, string $type): void
    {
        $filename = $output ?? "financial_report_{$type}_" . now()->format('Y-m-d_H-i-s');

        if ($export === 'csv') {
            $csvContent = $this->financialReportService->exportToCsv($report, $filename . '.csv');
            $filepath = storage_path("app/{$filename}.csv");
            file_put_contents($filepath, $csvContent);
            $this->info("📁 تم تصدير التقرير إلى: {$filepath}");
        } elseif ($export === 'json') {
            $filepath = storage_path("app/{$filename}.json");
            file_put_contents($filepath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info("📁 تم تصدير التقرير إلى: {$filepath}");
        }
    }
}
