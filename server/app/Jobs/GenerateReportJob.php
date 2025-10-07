<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\ExportedReport;
use App\Services\FinancialReportService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GenerateReportJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    protected int $reportId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $reportId)
    {
        $this->reportId = $reportId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $report = ExportedReport::find($this->reportId);
        
        if (!$report) {
            Log::error('GenerateReportJob: Report not found', ['report_id' => $this->reportId]);
            return;
        }

        try {
            $this->updateProgress($report, 10, 'Starting report generation...');
            
            // تحضير البيانات
            $reportService = app(FinancialReportService::class);
            $data = $this->generateReportData($report, $reportService);
            
            $this->updateProgress($report, 50, 'Generating report data...');
            
            // توليد الملف حسب التنسيق المطلوب
            $filePath = $this->generateFile($report, $data);
            
            $this->updateProgress($report, 80, 'Saving report file...');
            
            // حفظ مسار الملف و تحديث الحالة
            $fileSize = Storage::size($filePath);
            
            $report->update([
                'status' => 'completed',
                'file_path' => $filePath,
                'file_size' => $this->formatFileSize($fileSize),
                'completed_at' => now(),
                'progress_percentage' => 100
            ]);

            Log::info('Report generated successfully', [
                'report_id' => $this->reportId,
                'file_path' => $filePath,
                'format' => $report->format
            ]);

        } catch (\Exception $e) {
            Log::error('Report generation failed', [
                'report_id' => $this->reportId,
                'error' => $e->getMessage()
            ]);

            $report->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now()
            ]);
        }
    }

    /**
     * تحديث التقدم
     */
    private function updateProgress(ExportedReport $report, int $percentage, string $message): void
    {
        $report->update(['progress_percentage' => $percentage]);
        Log::info('Report progress update', [
            'report_id' => $report->id,
            'percentage' => $percentage,
            'message' => $message
        ]);
    }

    /**
     * توليد بيانات التقرير
     */
    private function generateReportData(ExportedReport $report, FinancialReportService $service): array
    {
        $filters = $report->filters ?? [];
        
        return match($report->report_type) {
            'monthly_revenue' => $service->getMonthlyRevenueReport($filters['year'] ?? null),
            'detailed_expenses' => $service->getDetailedRevenueReport($filters),
            'profit_loss' => $service->getNetProfitReport($filters),
            'comprehensive_financial' => $service->getRevenueReport($filters),
            'tax_report' => $service->getTaxReport($filters),
            'commissions' => $service->getCommissionReport($filters),
            default => throw new \InvalidArgumentException("Unsupported report type: {$report->report_type}")
        };
    }

    /**
     * توليد الملف حسب التنسيق
     */
    private function generateFile(ExportedReport $report, array $data): string
    {
        $filename = $this->generateFilename($report);
        
        return match($report->format) {
            'csv' => $this->generateCsvFile($report, $data, $filename),
            'pdf' => $this->generatePdfFile($report, $data, $filename),
            'excel' => $this->generateExcelFile($report, $data, $filename),
            default => throw new \InvalidArgumentException("Unsupported format: {$report->format}")
        };
    }

    /**
     * توليد اسم الملف
     */
    private function generateFilename(ExportedReport $report): string
    {
        $timestamp = Carbon::parse($report->created_at)->format('Y-m-d_H-i-s');
        return "reports/{$report->report_type}_{$timestamp}.{$report->format}";
    }

    /**
     * توليد ملف CSV
     */
    private function generateCsvFile(ExportedReport $report, array $data, string $filename): string
    {
        $csvContent = "\xEF\xBB\xBF"; // UTF-8 BOM for Arabic support
        
        // إضافة رؤساء الأعمدة حسب نوع التقرير
        $headers = $this->getCsvHeaders($report->report_type);
        $csvContent .= implode(',', $headers) . "\n";
        
        // إضافة البيانات
        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as $row) {
                $csvContent .= $this->formatCsvRow($row, $report->report_type) . "\n";
            }
        }
        
        Storage::put($filename, $csvContent);
        return $filename;
    }

    /**
     * توليد ملف PDF
     */
    private function generatePdfFile(ExportedReport $report, array $data, string $filename): string
    {
        // استخدام HTML template ثم تحويل إلى PDF
        $htmlContent = view('pdf.reports.' . $report->report_type, [
            'report' => $report,
            'data' => $data,
            'created_at' => now()->format('Y-m-d H:i:s')
        ])->render();
        
        // محاكاة توليد PDF بقالب HTML بسيط
        $pdfContent = "<!DOCTYPE html>
        <html dir='rtl'>
        <head>
            <meta charset='UTF-8'>
            <title>{$report->report_name}</title>
        </head>
        <body>
            <h1>{$report->report_name}</h1>
            <p>تم التوليد في: {$report->created_at}</p>
            <pre>" . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "</pre>
        </body>
        </html>";
        
        Storage::put($filename, $pdfContent);
        return $filename;
    }

    /**
     * توليد ملف Excel
     */
    private function generateExcelFile(ExportedReport $report, array $data, string $filename): string
    {
        // محاكاة توليد Excel بملف CSV محسن
        $excelContent = $this->generateCsvFile($report, $data, $filename);
        
        // يمكن استخدام مكتبات مثل PhpSpreadsheet هنا
        return $excelContent;
    }

    /**
     * الحصول على رؤساء CSV حسب نوع التقرير
     */
    private function getCsvHeaders(string $reportType): array
    {
        return match($reportType) {
            'detailed_expenses' => ['التاريخ', 'المصدر', 'رقم العميل', 'اسم العميل', 'النوع', 'المبلغ الأساسي', 'الضريبة', 'الإجمالي', 'الحالة'],
            'profit_loss' => ['الفئة', 'المصدر', 'المبلغ', 'النسبة المئوية'],
            'monthly_revenue' => ['الشهر', 'الإيرادات', 'العمولات', 'عدد الفواتير'],
            default => ['المعطيات']
        };
    }

    /**
     * تنسيق صف CSV
     */
    private function formatCsvRow(array $row, string $reportType): string
    {
        return '"' . implode('","', array_values($row)) . '"';
    }

    /**
     * تنسيق حجم الملف
     */
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
