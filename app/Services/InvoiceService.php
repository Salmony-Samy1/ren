<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InvoiceService
{
    protected $commissionService;
    protected $taxService;

    public function __construct()
    {
        $this->commissionService = app(CommissionService::class);
        $this->taxService = app(TaxService::class);
    }

    /**
     * إنشاء فاتورة للعميل (ضرائب/VAT فقط)
     */
    public function createCustomerInvoice(Booking $booking, User $customer): Invoice
    {
        try {
            DB::beginTransaction();

            // حساب الضرائب فقط للعميل
            $taxData = $this->taxService->calculateTax($booking);
            
            // إنشاء الفاتورة على مستوى الطلب إن وجد
            $orderId = $booking->order_id;
            $invoice = Invoice::create([
                'user_id' => $customer->id,
                'order_id' => $orderId,
                'booking_id' => $orderId ? null : $booking->id,
                'total_amount' => $booking->total,
                'tax_amount' => $taxData['total_tax'],
                'discount_amount' => $booking->discount ?? 0,
                'commission_amount' => 0, // لا يرى العميل العمولة
                'provider_amount' => 0, // لا يرى العميل مبلغ المزود
                'platform_amount' => 0, // لا يرى العميل مبلغ المنصة
                'invoice_type' => 'customer',
                'status' => 'paid',
                'payment_method' => $booking->payment_method,
                'transaction_id' => $booking->transaction_id,
            ]);

            // إضافة عناصر الفاتورة: لو كان الطلب موجوداً، نبني العناصر من OrderItems
            if ($orderId) {
                foreach ($booking->order->items as $oi) {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'description' => 'Service #' . $oi->service_id,
                        'quantity' => $oi->quantity,
                        'unit_price' => $oi->unit_price,
                        'total' => $oi->line_total,
                        'tax_rate' => 0,
                        'tax_amount' => $oi->tax,
                    ]);
                }
            } else {
                $this->createInvoiceItems($invoice, $booking);
            }

            DB::commit();
            return $invoice;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * إنشاء فاتورة لمقدم الخدمة (تفاصيل العمولة)
     */
    public function createProviderInvoice(Booking $booking, User $provider): Invoice
    {
        try {
            DB::beginTransaction();

            // حساب العمولة والضرائب
            $commissionData = $this->commissionService->calculateCommission($booking);
            $taxData = $this->taxService->calculateTax($booking);

            // إنشاء الفاتورة على مستوى الطلب إن وجد
            $orderId = $booking->order_id;
            $invoice = Invoice::create([
                'user_id' => $provider->id,
                'order_id' => $orderId,
                'booking_id' => $orderId ? null : $booking->id,
                'total_amount' => $booking->total,
                'tax_amount' => $taxData['total_tax'],
                'discount_amount' => $booking->discount ?? 0,
                'commission_amount' => $commissionData['total_commission'],
                'provider_amount' => $commissionData['provider_amount'],
                'platform_amount' => $commissionData['platform_amount'],
                'invoice_type' => 'provider',
                'status' => 'pending',
                'payment_method' => $booking->payment_method,
                'transaction_id' => $booking->transaction_id,
                'commission_breakdown' => json_encode([
                    'base_commission' => $commissionData['base_commission'],
                    'service_type_commission' => $commissionData['service_type_commission'],
                    'volume_commission' => $commissionData['volume_commission'],
                    'rating_commission' => $commissionData['rating_commission'],
                    'commission_rate' => $commissionData['commission_rate']
                ]),
            ]);

            // إضافة عناصر الفاتورة: لو كان الطلب موجوداً، نبني العناصر من OrderItems
            if ($orderId) {
                foreach ($booking->order->items as $oi) {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'description' => 'Service #' . $oi->service_id,
                        'quantity' => $oi->quantity,
                        'unit_price' => $oi->unit_price,
                        'total' => $oi->line_total,
                        'tax_rate' => 0,
                        'tax_amount' => $oi->tax,
                    ]);
                }
            } else {
                $this->createProviderInvoiceItems($invoice, $booking, $commissionData);
            }

            DB::commit();
            return $invoice;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * إنشاء عناصر فاتورة العميل
     */
    private function createInvoiceItems(Invoice $invoice, Booking $booking): void
    {
        $service = $booking->service;
        
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => $service->name,
            'quantity' => 1,
            'unit_price' => $booking->subtotal,
            'total' => $booking->subtotal,
            'tax_rate' => $this->taxService->getTaxRate($service),
            'tax_amount' => $invoice->tax_amount,
        ]);

        // إضافة الضرائب كعنصر منفصل
        if ($invoice->tax_amount > 0) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => 'VAT/Tax',
                'quantity' => 1,
                'unit_price' => $invoice->tax_amount,
                'total' => $invoice->tax_amount,
                'tax_rate' => 0,
                'tax_amount' => 0,
            ]);
        }

        // إضافة الخصم إذا وجد
        if ($invoice->discount_amount > 0) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => 'Discount',
                'quantity' => 1,
                'unit_price' => -$invoice->discount_amount,
                'total' => -$invoice->discount_amount,
                'tax_rate' => 0,
                'tax_amount' => 0,
            ]);
        }
    }

    /**
     * إنشاء عناصر فاتورة المزود مع تفاصيل العمولة
     */
    private function createProviderInvoiceItems(Invoice $invoice, Booking $booking, array $commissionData): void
    {
        $service = $booking->service;
        
        // عنصر الخدمة الأساسي
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => $service->name,
            'quantity' => 1,
            'unit_price' => $booking->subtotal,
            'total' => $booking->subtotal,
            'tax_rate' => $this->taxService->getTaxRate($service),
            'tax_amount' => $invoice->tax_amount,
        ]);

        // تفاصيل العمولة
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Platform Commission',
            'quantity' => 1,
            'unit_price' => -$commissionData['total_commission'],
            'total' => -$commissionData['total_commission'],
            'tax_rate' => 0,
            'tax_amount' => 0,
            'commission_breakdown' => json_encode([
                'base' => $commissionData['base_commission'],
                'service_type' => $commissionData['service_type_commission'],
                'volume' => $commissionData['volume_commission'],
                'rating' => $commissionData['rating_commission']
            ])
        ]);

        // الضرائب
        if ($invoice->tax_amount > 0) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => 'VAT/Tax',
                'quantity' => 1,
                'unit_price' => $invoice->tax_amount,
                'total' => $invoice->tax_amount,
                'tax_rate' => 0,
                'tax_amount' => 0,
            ]);
        }

        // الخصم
        if ($invoice->discount_amount > 0) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => 'Discount',
                'quantity' => 1,
                'unit_price' => -$invoice->discount_amount,
                'total' => -$invoice->discount_amount,
                'tax_rate' => 0,
                'tax_amount' => 0,
            ]);
        }

        // صافي المبلغ للمزود
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Net Amount to Provider',
            'quantity' => 1,
            'unit_price' => $commissionData['provider_amount'],
            'total' => $commissionData['provider_amount'],
            'tax_rate' => 0,
            'tax_amount' => 0,
        ]);
    }

    /**
     * الحصول على فاتورة العميل (بدون تفاصيل العمولة)
     */
    public function getCustomerInvoice(int $invoiceId, User $customer): ?Invoice
    {
        return Invoice::where('id', $invoiceId)
            ->where('user_id', $customer->id)
            ->where('invoice_type', 'customer')
            ->with(['items', 'booking.service'])
            ->first();
    }

    /**
     * الحصول على فاتورة المزود (مع تفاصيل العمولة)
     */
    public function getProviderInvoice(int $invoiceId, User $provider): ?Invoice
    {
        return Invoice::where('id', $invoiceId)
            ->where('user_id', $provider->id)
            ->where('invoice_type', 'provider')
            ->with(['items', 'booking.service'])
            ->first();
    }

    /**
     * تحديث حالة الفاتورة
     */
    public function updateInvoiceStatus(int $invoiceId, string $status): bool
    {
        $invoice = Invoice::findOrFail($invoiceId);
        return $invoice->update(['status' => $status]);
    }

    /**
     * إلغاء الفاتورة
     */
    public function cancelInvoice(int $invoiceId): bool
    {
        $invoice = Invoice::findOrFail($invoiceId);
        
        if ($invoice->status === 'paid') {
            // إعادة المال للعميل
            $this->refundCustomer($invoice);
        }
        
        return $invoice->update(['status' => 'cancelled']);
    }

    /**
     * إعادة المال للعميل
     */
    private function refundCustomer(Invoice $invoice): void
    {
        $customer = $invoice->user;
        $customer->deposit($invoice->total_amount, [
            'description' => "Refund for cancelled invoice #{$invoice->id}",
            'invoice_id' => $invoice->id,
            'type' => 'refund'
        ]);
    }

    /**
     * الحصول على إحصائيات الفواتير
     */
    public function getInvoiceStats(User $user, string $period = 'month'): array
    {
        $startDate = $this->getStartDate($period);
        $endDate = Carbon::now();

        $query = Invoice::where('user_id', $user->id)
            ->whereBetween('created_at', [$startDate, $endDate]);

        $stats = [
            'total_invoices' => $query->count(),
            'total_amount' => $query->sum('total_amount'),
            'total_tax' => $query->sum('tax_amount'),
            'total_discount' => $query->sum('discount_amount'),
            'paid_invoices' => $query->where('status', 'paid')->count(),
            'pending_invoices' => $query->where('status', 'pending')->count(),
            'cancelled_invoices' => $query->where('status', 'cancelled')->count(),
        ];

        // إضافة إحصائيات العمولة للمزودين
        if ($user->type === 'provider') {
            $stats['total_commission'] = $query->sum('commission_amount');
            $stats['total_provider_amount'] = $query->sum('provider_amount');
            $stats['total_platform_amount'] = $query->sum('platform_amount');
        }

        return $stats;
    }

    /**
     * الحصول على تاريخ البداية حسب الفترة
     */
    private function getStartDate(string $period): Carbon
    {
        return match($period) {
            'week' => Carbon::now()->subWeek(),
            'month' => Carbon::now()->subMonth(),
            'quarter' => Carbon::now()->subQuarter(),
            'year' => Carbon::now()->subYear(),
            default => Carbon::now()->subMonth(),
        };
    }

    /**
     * تصدير الفاتورة إلى PDF
     */
    public function exportToPdf(Invoice $invoice): string
    {
        // هنا يمكن إضافة منطق تصدير PDF
        // يمكن استخدام مكتبة مثل DomPDF أو Snappy
        return "PDF content for invoice #{$invoice->id}";
    }

    /**
     * إرسال الفاتورة عبر البريد الإلكتروني
     */
    public function sendInvoiceEmail(Invoice $invoice): bool
    {
        try {
            // إرسال الفاتورة عبر البريد الإلكتروني
            $user = $invoice->user;
            $emailService = app(\App\Services\EmailService::class);
            
            return $emailService->sendInvoice($user->email, $invoice);
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }
}
