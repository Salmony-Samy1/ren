<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\InvoiceService;
use App\Models\Invoice;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    protected $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * عرض قائمة الفواتير
     */
    public function index(Request $request)
    {
        $filters = $request->only(['status', 'type', 'user_type', 'period', 'search']);
        
        $query = Invoice::with(['user', 'booking.service']);
        
        // فلترة حسب الحالة
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        // فلترة حسب نوع الفاتورة
        if (isset($filters['type'])) {
            $query->where('invoice_type', $filters['type']);
        }
        
        // فلترة حسب نوع المستخدم
        if (isset($filters['user_type'])) {
            $query->whereHas('user', function($q) use ($filters) {
                $q->where('type', $filters['user_type']);
            });
        }
        
        // فلترة حسب الفترة
        if (isset($filters['period'])) {
            $startDate = $this->getStartDate($filters['period']);
            $query->where('created_at', '>=', $startDate);
        }
        
        // البحث
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('transaction_id', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQ) use ($search) {
                      $userQ->where('full_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('booking.service', function($serviceQ) use ($search) {
                      $serviceQ->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        $invoices = $query->orderBy('created_at', 'desc')->paginate(20);
        
        // إحصائيات عامة
        $stats = $this->getGeneralStats();
        
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
    public function show($id)
    {
        $invoice = Invoice::with(['user', 'booking.service', 'items'])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $invoice
        ]);
    }

    /**
     * إنشاء فاتورة جديدة
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'order_id' => 'sometimes|nullable|exists:orders,id',
            'booking_id' => 'required_without:order_id|nullable|exists:bookings,id',
            'invoice_type' => 'required|in:customer,provider',
            'total_amount' => 'required|numeric|min:0',
            'tax_amount' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'commission_amount' => 'nullable|numeric|min:0',
            'provider_amount' => 'nullable|numeric|min:0',
            'platform_amount' => 'nullable|numeric|min:0',
            'status' => 'required|in:pending,paid,cancelled',
            'payment_method' => 'nullable|string',
            'notes' => 'nullable|string'
        ], [
            'user_id.required' => 'معرف المستخدم مطلوب',
            'user_id.exists' => 'المستخدم غير موجود',
            'booking_id.required_without' => 'يلزم تحديد معرف الطلب أو الحجز',
            'booking_id.exists' => 'الحجز غير موجود',
            'order_id.exists' => 'الطلب غير موجود',
            'invoice_type.required' => 'نوع الفاتورة مطلوب',
            'invoice_type.in' => 'نوع الفاتورة غير صحيح',
            'total_amount.required' => 'المبلغ الإجمالي مطلوب',
            'total_amount.numeric' => 'المبلغ الإجمالي يجب أن يكون رقماً',
            'total_amount.min' => 'المبلغ الإجمالي يجب أن يكون أكبر من صفر',
            'tax_amount.required' => 'مبلغ الضريبة مطلوب',
            'tax_amount.numeric' => 'مبلغ الضريبة يجب أن يكون رقماً',
            'tax_amount.min' => 'مبلغ الضريبة يجب أن يكون أكبر من أو يساوي صفر',
            'status.required' => 'حالة الفاتورة مطلوبة',
            'status.in' => 'حالة الفاتورة غير صحيحة'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $payload = $request->all();

            // تفضيل إنشاء الفاتورة على مستوى الطلب
            $orderId = $payload['order_id'] ?? null;
            if (!$orderId && !empty($payload['booking_id'])) {
                $bk = Booking::find($payload['booking_id']);
                if ($bk && $bk->order_id) {
                    $orderId = $bk->order_id;
                }
            }

            if ($orderId) {
                // أجبر الفاتورة أن ترتبط بالطلب فقط
                $payload['order_id'] = $orderId;
                $payload['booking_id'] = null;

                // توليد رقم فاتورة إذا لم يُرسل
                if (empty($payload['invoice_number'])) {
                    $seq = str_pad((string) (\App\Models\Invoice::max('id') + 1), 5, '0', STR_PAD_LEFT);
                    $payload['invoice_number'] = 'INV-' . now()->format('Ymd') . '-' . $seq;
                }

                $invoice = Invoice::create($payload);

                // في حال لم تُرسل عناصر الفاتورة يدوياً، ابنيها من عناصر الطلب
                if (!$request->has('items') || !is_array($request->items)) {
                    $order = \App\Models\Order::with('items')->findOrFail($orderId);
                    foreach ($order->items as $oi) {
                        $invoice->items()->create([
                            'description' => 'Service #' . $oi->service_id,
                            'quantity' => $oi->quantity,
                            'unit_price' => $oi->unit_price,
                            'total' => $oi->line_total,
                            'tax_rate' => 0,
                            'tax_amount' => $oi->tax,
                        ]);
                    }
                } else {
                    // إن قُدمت عناصر يدوياً، نُسجلها كما هي (استخدام إداري خاص)
                    foreach ($request->items as $item) {
                        $invoice->items()->create([
                            'description' => $item['description'],
                            'quantity' => $item['quantity'] ?? 1,
                            'unit_price' => $item['unit_price'],
                            'total' => $item['total'],
                            'tax_rate' => $item['tax_rate'] ?? 0,
                            'tax_amount' => $item['tax_amount'] ?? 0,
                        ]);
                    }
                }
            } else {
                // fallback القديم على مستوى الحجز عندما لا يوجد Order
                $invoice = Invoice::create($payload);

                if ($request->has('items') && is_array($request->items)) {
                    foreach ($request->items as $item) {
                        $invoice->items()->create([
                            'description' => $item['description'],
                            'quantity' => $item['quantity'] ?? 1,
                            'unit_price' => $item['unit_price'],
                            'total' => $item['total'],
                            'tax_rate' => $item['tax_rate'] ?? 0,
                            'tax_amount' => $item['tax_amount'] ?? 0,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الفاتورة بنجاح',
                'data' => $invoice->load(['user', 'booking.service', 'items'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء الفاتورة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تحديث فاتورة
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'total_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'commission_amount' => 'nullable|numeric|min:0',
            'provider_amount' => 'nullable|numeric|min:0',
            'platform_amount' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:pending,paid,cancelled',
            'payment_method' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $invoice = Invoice::findOrFail($id);
            $invoice->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الفاتورة بنجاح',
                'data' => $invoice->load(['user', 'booking.service', 'items'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الفاتورة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * حذف فاتورة
     */
    public function destroy($id)
    {
        try {
            $invoice = Invoice::findOrFail($id);
            $invoice->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الفاتورة بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الفاتورة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تحديث حالة الفاتورة
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:paid,pending,cancelled',
            'notes' => 'nullable|string'
        ], [
            'status.required' => 'حالة الفاتورة مطلوبة',
            'status.in' => 'حالة الفاتورة غير صحيحة'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $invoice = Invoice::findOrFail($id);
            
            if ($request->status === 'cancelled') {
                $invoice->markAsCancelled($request->notes);
            } elseif ($request->status === 'paid') {
                $invoice->markAsPaid();
            } else {
                $invoice->update(['status' => $request->status]);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث حالة الفاتورة بنجاح',
                'data' => $invoice->load(['user', 'booking.service', 'items'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث حالة الفاتورة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * إلغاء فاتورة
     */
    public function cancel(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $invoice = Invoice::findOrFail($id);
            
            if ($invoice->status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'الفاتورة ملغية بالفعل'
                ], 400);
            }

            $success = $this->invoiceService->cancelInvoice($id);

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
     * تصدير الفواتير إلى CSV
     */
    public function exportCsv(Request $request)
    {
        $filters = $request->only(['status', 'type', 'user_type', 'period']);
        
        $query = Invoice::with(['user', 'booking.service']);
        
        // تطبيق الفلاتر
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['type'])) {
            $query->where('invoice_type', $filters['type']);
        }
        if (isset($filters['user_type'])) {
            $query->whereHas('user', function($q) use ($filters) {
                $q->where('type', $filters['user_type']);
            });
        }
        if (isset($filters['period'])) {
            $startDate = $this->getStartDate($filters['period']);
            $query->where('created_at', '>=', $startDate);
        }
        
        $invoices = $query->get();
        
        $csvData = $this->generateCsvData($invoices);
        
        return response()->json([
            'success' => true,
            'data' => [
                'csv_content' => $csvData,
                'filename' => 'invoices_' . date('Y-m-d') . '.csv'
            ]
        ]);
    }

    /**
     * الحصول على إحصائيات عامة
     */
    private function getGeneralStats(): array
    {
        $totalInvoices = Invoice::count();
        $totalRevenue = Invoice::sum('total_amount');
        $totalTax = Invoice::sum('tax_amount');
        $totalCommission = Invoice::sum('commission_amount');
        
        $stats = [
            'total_invoices' => $totalInvoices,
            'total_revenue' => $totalRevenue,
            'total_tax' => $totalTax,
            'total_commission' => $totalCommission,
            'by_status' => [
                'paid' => Invoice::where('status', 'paid')->count(),
                'pending' => Invoice::where('status', 'pending')->count(),
                'cancelled' => Invoice::where('status', 'cancelled')->count(),
            ],
            'by_type' => [
                'customer' => Invoice::where('invoice_type', 'customer')->count(),
                'provider' => Invoice::where('invoice_type', 'provider')->count(),
            ],
            'by_period' => [
                'today' => Invoice::whereDate('created_at', today())->count(),
                'this_week' => Invoice::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'this_month' => Invoice::whereMonth('created_at', now()->month)->count(),
                'this_year' => Invoice::whereYear('created_at', now()->year)->count(),
            ]
        ];
        
        return $stats;
    }

    /**
     * توليد بيانات CSV
     */
    private function generateCsvData($invoices): string
    {
        $headers = [
            'ID', 'نوع الفاتورة', 'المستخدم', 'البريد الإلكتروني', 'نوع المستخدم',
            'المبلغ الإجمالي', 'الضريبة', 'الخصم', 'العمولة', 'مبلغ المزود',
            'مبلغ المنصة', 'الحالة', 'طريقة الدفع', 'تاريخ الإنشاء'
        ];
        
        $rows = [];
        foreach ($invoices as $invoice) {
            $rows[] = [
                $invoice->id,
                $invoice->invoice_type,
                $invoice->user->full_name ?? 'غير محدد',
                $invoice->user->email,
                $invoice->user->type,
                $invoice->total_amount,
                $invoice->tax_amount,
                $invoice->discount_amount,
                $invoice->commission_amount,
                $invoice->provider_amount,
                $invoice->platform_amount,
                $invoice->status,
                $invoice->payment_method ?? 'غير محدد',
                $invoice->created_at->format('Y-m-d H:i:s')
            ];
        }
        
        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, $headers);
        foreach ($rows as $row) {
            fputcsv($csv, $row);
        }
        rewind($csv);
        $csvContent = stream_get_contents($csv);
        fclose($csv);
        
        return $csvContent;
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
