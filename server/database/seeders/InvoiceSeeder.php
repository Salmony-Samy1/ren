<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Invoice;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 بدء إنشاء الفواتير التجريبية...');

        // الحصول على المستخدمين والخدمات
        $users = User::where('type', 'customer')->get();
        $services = Service::all();
        $bookings = Booking::all();

        if ($users->isEmpty() || $services->isEmpty() || $bookings->isEmpty()) {
            $this->command->warn('⚠️ يجب إنشاء المستخدمين والخدمات والحجوزات أولاً');
            return;
        }

        $invoices = [];
        $now = now();

        // إنشاء فواتير للشهر الحالي
        for ($i = 0; $i < 50; $i++) {
            $booking = $bookings->random();
            $service = $services->where('id', $booking->service_id)->first();
            $customer = $users->random();
            
            if (!$service) continue;

            $subtotal = $service->price ?? rand(100, 1000);
            $taxAmount = $subtotal * 0.15; // 15% ضريبة
            $discountAmount = rand(0, 1) ? rand(10, 100) : 0; // خصم عشوائي
            $commissionAmount = $subtotal * 0.10; // 10% عمولة
            $totalAmount = $subtotal + $taxAmount - $discountAmount;
            $providerAmount = $totalAmount - $commissionAmount;
            $platformAmount = $commissionAmount;

            $invoices[] = [
                'user_id' => $customer->id,
                'booking_id' => $booking->id,
                'total_amount' => $totalAmount,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'commission_amount' => $commissionAmount,
                'provider_amount' => $providerAmount,
                'platform_amount' => $platformAmount,
                'commission_breakdown' => json_encode([
                    'service_commission' => $commissionAmount * 0.8,
                    'platform_fee' => $commissionAmount * 0.2
                ]),
                'created_at' => $now->copy()->subDays(rand(0, 30)),
                'updated_at' => $now->copy()->subDays(rand(0, 30))
            ];
        }

        // إنشاء فواتير للشهر السابق
        for ($i = 0; $i < 30; $i++) {
            $booking = $bookings->random();
            $service = $services->where('id', $booking->service_id)->first();
            $customer = $users->random();
            
            if (!$service) continue;

            $subtotal = $service->price ?? rand(100, 1000);
            $taxAmount = $subtotal * 0.15;
            $discountAmount = rand(0, 1) ? rand(10, 100) : 0;
            $commissionAmount = $subtotal * 0.10;
            $totalAmount = $subtotal + $taxAmount - $discountAmount;
            $providerAmount = $totalAmount - $commissionAmount;
            $platformAmount = $commissionAmount;

            $invoices[] = [
                'user_id' => $customer->id,
                'booking_id' => $booking->id,
                'total_amount' => $totalAmount,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'commission_amount' => $commissionAmount,
                'provider_amount' => $providerAmount,
                'platform_amount' => $platformAmount,
                'commission_breakdown' => json_encode([
                    'service_commission' => $commissionAmount * 0.8,
                    'platform_fee' => $commissionAmount * 0.2
                ]),
                'created_at' => $now->copy()->subMonth()->subDays(rand(0, 30)),
                'updated_at' => $now->copy()->subMonth()->subDays(rand(0, 30))
            ];
        }

        // إنشاء فواتير للشهرين السابقين
        for ($i = 0; $i < 20; $i++) {
            $booking = $bookings->random();
            $service = $services->where('id', $booking->service_id)->first();
            $customer = $users->random();
            
            if (!$service) continue;

            $subtotal = $service->price ?? rand(100, 1000);
            $taxAmount = $subtotal * 0.15;
            $discountAmount = rand(0, 1) ? rand(10, 100) : 0;
            $commissionAmount = $subtotal * 0.10;
            $totalAmount = $subtotal + $taxAmount - $discountAmount;
            $providerAmount = $totalAmount - $commissionAmount;
            $platformAmount = $commissionAmount;

            $invoices[] = [
                'user_id' => $customer->id,
                'booking_id' => $booking->id,
                'total_amount' => $totalAmount,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'commission_amount' => $commissionAmount,
                'provider_amount' => $providerAmount,
                'platform_amount' => $platformAmount,
                'commission_breakdown' => json_encode([
                    'service_commission' => $commissionAmount * 0.8,
                    'platform_fee' => $commissionAmount * 0.2
                ]),
                'created_at' => $now->copy()->subMonths(2)->subDays(rand(0, 30)),
                'updated_at' => $now->copy()->subMonths(2)->subDays(rand(0, 30))
            ];
        }

        // إنشاء فواتير للشهور السابقة (للتقارير السنوية)
        for ($month = 3; $month <= 11; $month++) {
            for ($i = 0; $i < rand(15, 25); $i++) {
                $booking = $bookings->random();
                $service = $services->where('id', $booking->service_id)->first();
                $customer = $users->random();
                
                if (!$service) continue;

                $subtotal = $service->price ?? rand(100, 1000);
                $taxAmount = $subtotal * 0.15;
                $discountAmount = rand(0, 1) ? rand(10, 100) : 0;
                $commissionAmount = $subtotal * 0.10;
                $totalAmount = $subtotal + $taxAmount - $discountAmount;
                $providerAmount = $totalAmount - $commissionAmount;
                $platformAmount = $commissionAmount;

                $invoices[] = [
                    'user_id' => $customer->id,
                    'booking_id' => $booking->id,
                    'total_amount' => $totalAmount,
                    'tax_amount' => $taxAmount,
                    'discount_amount' => $discountAmount,
                    'commission_amount' => $commissionAmount,
                    'provider_amount' => $providerAmount,
                    'platform_amount' => $platformAmount,
                    'commission_breakdown' => json_encode([
                        'service_commission' => $commissionAmount * 0.8,
                        'platform_fee' => $commissionAmount * 0.2
                    ]),
                    'created_at' => $now->copy()->subMonths($month)->subDays(rand(0, 30)),
                    'updated_at' => $now->copy()->subMonths($month)->subDays(rand(0, 30))
                ];
            }
        }

        // إدراج الفواتير
        DB::table('invoices')->insert($invoices);

        $this->command->info("✅ تم إنشاء " . count($invoices) . " فاتورة تجريبية بنجاح!");

        // عرض إحصائيات سريعة
        $totalRevenue = DB::table('invoices')->sum('total_amount');
        $totalCommission = DB::table('invoices')->sum('commission_amount');
        $totalTax = DB::table('invoices')->sum('tax_amount');
        $totalDiscount = DB::table('invoices')->sum('discount_amount');

        $this->command->info("📊 إحصائيات الفواتير:");
        $this->command->table(
            ['المقياس', 'القيمة'],
            [
                ['إجمالي الإيرادات', number_format($totalRevenue, 2) . ' ريال'],
                ['إجمالي العمولات', number_format($totalCommission, 2) . ' ريال'],
                ['إجمالي الضرائب', number_format($totalTax, 2) . ' ريال'],
                ['إجمالي الخصومات', number_format($totalDiscount, 2) . ' ريال'],
                ['عدد الفواتير', count($invoices)]
            ]
        );
    }
}
