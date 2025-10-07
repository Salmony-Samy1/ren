<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Booking;
use App\Models\User;
use Carbon\Carbon;

class InvoiceTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 بدء إنشاء فواتير اختبارية...');

        // الحصول على البيانات المطلوبة
        $customers = User::where('type', 'customer')->get();
        $providers = User::where('type', 'provider')->get();
        $bookings = Booking::with(['service'])->get();

        if ($customers->isEmpty() || $providers->isEmpty() || $bookings->isEmpty()) {
            $this->command->warn('⚠️ يجب إنشاء المستخدمين والحجوزات أولاً');
            return;
        }

        $this->command->info('📝 إنشاء فواتير العملاء...');
        $this->createCustomerInvoices($customers, $bookings);

        $this->command->info('📝 إنشاء فواتير المزودين...');
        $this->createProviderInvoices($providers, $bookings);

        $this->command->info('✅ تم إنشاء الفواتير الاختبارية بنجاح!');
        
        // عرض إحصائيات سريعة
        $this->displayStats();
    }

    /**
     * إنشاء فواتير العملاء
     */
    private function createCustomerInvoices($customers, $bookings): void
    {
        foreach ($bookings->take(10) as $booking) {
            $customer = $customers->random();
            
            $invoice = Invoice::create([
                'user_id' => $customer->id,
                'booking_id' => $booking->id,
                'total_amount' => $booking->total,
                'tax_amount' => $booking->tax ?? 0,
                'discount_amount' => $booking->discount ?? 0,
                'commission_amount' => 0,
                'provider_amount' => 0,
                'platform_amount' => 0,
                'invoice_type' => 'customer',
                'status' => 'paid',
                'payment_method' => $booking->payment_method ?? 'wallet',
                'transaction_id' => $booking->transaction_id ?? 'TXN' . uniqid(),
                'created_at' => $booking->created_at,
                'updated_at' => $booking->created_at,
            ]);

            // إنشاء عناصر الفاتورة
            $this->createInvoiceItems($invoice, $booking, 'customer');
        }
    }

    /**
     * إنشاء فواتير المزودين
     */
    private function createProviderInvoices($providers, $bookings): void
    {
        foreach ($bookings->take(10) as $booking) {
            $provider = $providers->random();
            
            // حساب العمولة
            $commissionAmount = ($booking->total * 0.10); // 10% عمولة
            $providerAmount = $booking->total - $commissionAmount;
            
            $invoice = Invoice::create([
                'user_id' => $provider->id,
                'booking_id' => $booking->id,
                'total_amount' => $booking->total,
                'tax_amount' => $booking->tax ?? 0,
                'discount_amount' => $booking->discount ?? 0,
                'commission_amount' => $commissionAmount,
                'provider_amount' => $providerAmount,
                'platform_amount' => $commissionAmount,
                'invoice_type' => 'provider',
                'status' => 'pending',
                'payment_method' => $booking->payment_method ?? 'wallet',
                'transaction_id' => $booking->transaction_id ?? 'TXN' . uniqid(),
                'commission_breakdown' => json_encode([
                    'base_commission' => $commissionAmount * 0.8,
                    'service_type_commission' => $commissionAmount * 0.1,
                    'volume_commission' => $commissionAmount * 0.05,
                    'rating_commission' => $commissionAmount * 0.05,
                    'commission_rate' => 10.0
                ]),
                'created_at' => $booking->created_at,
                'updated_at' => $booking->created_at,
            ]);

            // إنشاء عناصر الفاتورة
            $this->createInvoiceItems($invoice, $booking, 'provider', $commissionAmount);
        }
    }

    /**
     * إنشاء عناصر الفاتورة
     */
    private function createInvoiceItems($invoice, $booking, $type, $commissionAmount = 0): void
    {
        // عنصر الخدمة الأساسي
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => $booking->service->name ?? 'خدمة غير محددة',
            'quantity' => 1,
            'unit_price' => $booking->subtotal ?? $booking->total,
            'total' => $booking->subtotal ?? $booking->total,
            'tax_rate' => 15.0,
            'tax_amount' => $booking->tax ?? 0,
        ]);

        // الضرائب
        if (($booking->tax ?? 0) > 0) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => 'VAT/Tax',
                'quantity' => 1,
                'unit_price' => $booking->tax,
                'total' => $booking->tax,
                'tax_rate' => 0,
                'tax_amount' => 0,
            ]);
        }

        // الخصم
        if (($booking->discount ?? 0) > 0) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => 'Discount',
                'quantity' => 1,
                'unit_price' => -$booking->discount,
                'total' => -$booking->discount,
                'tax_rate' => 0,
                'tax_amount' => 0,
            ]);
        }

        // تفاصيل العمولة للمزودين فقط
        if ($type === 'provider' && $commissionAmount > 0) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => 'Platform Commission',
                'quantity' => 1,
                'unit_price' => -$commissionAmount,
                'total' => -$commissionAmount,
                'tax_rate' => 0,
                'tax_amount' => 0,
                'commission_breakdown' => json_encode([
                    'base' => $commissionAmount * 0.8,
                    'service_type' => $commissionAmount * 0.1,
                    'volume' => $commissionAmount * 0.05,
                    'rating' => $commissionAmount * 0.05
                ])
            ]);

            // صافي المبلغ للمزود
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => 'Net Amount to Provider',
                'quantity' => 1,
                'unit_price' => $invoice->provider_amount,
                'total' => $invoice->provider_amount,
                'tax_rate' => 0,
                'tax_amount' => 0,
            ]);
        }
    }

    /**
     * عرض الإحصائيات
     */
    private function displayStats(): void
    {
        $totalInvoices = Invoice::count();
        $customerInvoices = Invoice::where('invoice_type', 'customer')->count();
        $providerInvoices = Invoice::where('invoice_type', 'provider')->count();
        $paidInvoices = Invoice::where('status', 'paid')->count();
        $pendingInvoices = Invoice::where('status', 'pending')->count();
        $totalRevenue = Invoice::sum('total_amount');
        $totalCommission = Invoice::sum('commission_amount');

        $this->command->info("📊 إحصائيات الفواتير:");
        $this->command->table(
            ['المقياس', 'القيمة'],
            [
                ['إجمالي الفواتير', $totalInvoices],
                ['فواتير العملاء', $customerInvoices],
                ['فواتير المزودين', $providerInvoices],
                ['الفواتير المدفوعة', $paidInvoices],
                ['الفواتير المعلقة', $pendingInvoices],
                ['إجمالي الإيرادات', number_format($totalRevenue, 2) . ' ريال'],
                ['إجمالي العمولات', number_format($totalCommission, 2) . ' ريال'],
            ]
        );
    }
}
