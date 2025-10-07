<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\InvoiceService;
use App\Models\Invoice;
use App\Models\Booking;
use App\Http\Requests\Invoice\StoreInvoiceRequest;
use App\Http\Requests\Invoice\UpdateInvoiceStatusRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class InvoiceController extends Controller
{
    protected $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * عرض قائمة الفواتير للمستخدم
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $filters = $request->only(['status', 'type', 'period']);
        
        $query = Invoice::where('user_id', $user->id);
        
        // فلترة حسب الحالة
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        // فلترة حسب نوع الفاتورة
        if (isset($filters['type'])) {
            $query->where('invoice_type', $filters['type']);
        }
        
        // فلترة حسب الفترة
        if (isset($filters['period'])) {
            $startDate = $this->getStartDate($filters['period']);
            $query->where('created_at', '>=', $startDate);
        }
        
        $invoices = $query->with(['booking.service', 'items'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        // إحصائيات سريعة
        $stats = $this->invoiceService->getInvoiceStats($user, $filters['period'] ?? 'month');
        
        return response()->json([
            'success' => true,
            'data' => [
                'invoices' => $invoices,
                'statistics' => $stats
            ]
        ]);
    }

    /**
     * عرض فاتورة محددة
     */
    public function show(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        return response()->json([
            'success' => true,
            'data' => $invoice->load(['items', 'booking.service'])
        ]);
    }

    /**
     * إنشاء فاتورة جديدة
     */
    public function store(StoreInvoiceRequest $request)
    {
        try {
            $user = Auth::user();
            $booking = Booking::with(['service.user'])->findOrFail($request->booking_id);

            // Authorization via policies on booking->user for customer and service->user for provider
            if ($request->invoice_type === 'customer') {
                $this->authorize('view', $booking); // customer must own booking
                $invoice = $this->invoiceService->createCustomerInvoice($booking, $user);
            } else {
                // provider must own the service
                if ($booking->service->user_id !== $user->id && $user->type !== 'admin') {
                    return response()->json([
                        'success' => false,
                        'message' => 'غير مصرح لك بإنشاء فاتورة لهذا الحجز'
                    ], 403);
                }
                $invoice = $this->invoiceService->createProviderInvoice($booking, $user);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الفاتورة بنجاح',
                'data' => $invoice->load(['items', 'booking.service'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء الفاتورة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تحديث حالة الفاتورة
     */
    public function updateStatus(UpdateInvoiceStatusRequest $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        try {
            $success = $this->invoiceService->updateInvoiceStatus($invoice->id, $request->status);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم تحديث حالة الفاتورة بنجاح'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث حالة الفاتورة'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث حالة الفاتورة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * إلغاء الفاتورة
     */
    public function cancel(Invoice $invoice)
    {
        $this->authorize('cancel', $invoice);

        if ($invoice->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'الفاتورة ملغية بالفعل'
            ], 400);
        }

        try {
            $success = $this->invoiceService->cancelInvoice($invoice->id);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم إلغاء الفاتورة بنجاح'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إلغاء الفاتورة'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إلغاء الفاتورة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تصدير الفاتورة إلى PDF
     */
    public function exportPdf($id)
    {
        try {
            $user = Auth::user();
            $invoice = Invoice::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'الفاتورة غير موجودة'
                ], 404);
            }

            $pdfContent = $this->invoiceService->exportToPdf($invoice);

            return response()->json([
                'success' => true,
                'data' => [
                    'pdf_content' => $pdfContent,
                    'filename' => "invoice_{$invoice->id}.pdf"
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تصدير الفاتورة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * إرسال الفاتورة عبر البريد الإلكتروني
     */
    public function sendEmail($id)
    {
        try {
            $user = Auth::user();
            $invoice = Invoice::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'الفاتورة غير موجودة'
                ], 404);
            }

            $success = $this->invoiceService->sendInvoiceEmail($invoice);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم إرسال الفاتورة عبر البريد الإلكتروني بنجاح'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إرسال الفاتورة'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إرسال الفاتورة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * الحصول على إحصائيات الفواتير
     */
    public function statistics(Request $request)
    {
        $user = Auth::user();
        $period = $request->get('period', 'month');
        
        $stats = $this->invoiceService->getInvoiceStats($user, $period);
        
        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * الحصول على تاريخ البداية حسب الفترة
     */
    private function getStartDate(string $period): \Carbon\Carbon
    {
        return match($period) {
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'quarter' => now()->subQuarter(),
            'year' => now()->subYear(),
            default => now()->subMonth(),
        };
    }
}
