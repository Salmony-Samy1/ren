<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;

class BookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 بدء إنشاء الحجوزات التجريبية...');

        // الحصول على المستخدمين والخدمات
        $customers = User::where('type', 'customer')->get();
        $services = Service::all();

        if ($customers->isEmpty() || $services->isEmpty()) {
            $this->command->warn('⚠️ يجب إنشاء المستخدمين والخدمات أولاً');
            return;
        }

        $bookings = [];
        $now = now();

        // إنشاء حجوزات للشهر الحالي
        for ($i = 0; $i < 30; $i++) {
            $service = $services->random();
            $customer = $customers->random();
            
            $subtotal = $service->price ?? rand(100, 1000);
            $tax = $subtotal * 0.15; // 15% ضريبة
            $discount = rand(0, 1) ? rand(10, 100) : 0; // خصم عشوائي
            $total = $subtotal + $tax - $discount;

            $startDate = $now->copy()->addDays(rand(1, 30));
            $endDate = $startDate->copy()->addHours(rand(1, 8));

            $bookings[] = [
                'service_id' => $service->id,
                'user_id' => $customer->id,
                'tax' => $tax,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'status' => $this->getRandomStatus(),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'booking_details' => json_encode([
                    'notes' => 'حجز تجريبي',
                    'requirements' => 'متطلبات خاصة',
                    'special_requests' => 'طلبات خاصة'
                ]),
                'payment_method' => $this->getRandomPaymentMethod(),
                'transaction_id' => 'TXN' . strtoupper(uniqid()),
                'is_paid' => rand(0, 1),
                'created_at' => $now->copy()->subDays(rand(0, 30)),
                'updated_at' => $now->copy()->subDays(rand(0, 30))
            ];
        }

        // إنشاء حجوزات للشهر السابق
        for ($i = 0; $i < 20; $i++) {
            $service = $services->random();
            $customer = $customers->random();
            
            $subtotal = $service->price ?? rand(100, 1000);
            $tax = $subtotal * 0.15;
            $discount = rand(0, 1) ? rand(10, 100) : 0;
            $total = $subtotal + $tax - $discount;

            $startDate = $now->copy()->subMonth()->addDays(rand(1, 30));
            $endDate = $startDate->copy()->addHours(rand(1, 8));

            $bookings[] = [
                'service_id' => $service->id,
                'user_id' => $customer->id,
                'tax' => $tax,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'status' => $this->getRandomStatus(),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'booking_details' => json_encode([
                    'notes' => 'حجز تجريبي',
                    'requirements' => 'متطلبات خاصة',
                    'special_requests' => 'طلبات خاصة'
                ]),
                'payment_method' => $this->getRandomPaymentMethod(),
                'transaction_id' => 'TXN' . strtoupper(uniqid()),
                'is_paid' => rand(0, 1),
                'created_at' => $now->copy()->subMonth()->subDays(rand(0, 30)),
                'updated_at' => $now->copy()->subMonth()->subDays(rand(0, 30))
            ];
        }

        // إنشاء حجوزات للشهرين السابقين
        for ($i = 0; $i < 15; $i++) {
            $service = $services->random();
            $customer = $customers->random();
            
            $subtotal = $service->price ?? rand(100, 1000);
            $tax = $subtotal * 0.15;
            $discount = rand(0, 1) ? rand(10, 100) : 0;
            $total = $subtotal + $tax - $discount;

            $startDate = $now->copy()->subMonths(2)->addDays(rand(1, 30));
            $endDate = $startDate->copy()->addHours(rand(1, 8));

            $bookings[] = [
                'service_id' => $service->id,
                'user_id' => $customer->id,
                'tax' => $tax,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'status' => $this->getRandomStatus(),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'booking_details' => json_encode([
                    'notes' => 'حجز تجريبي',
                    'requirements' => 'متطلبات خاصة',
                    'special_requests' => 'طلبات خاصة'
                ]),
                'payment_method' => $this->getRandomPaymentMethod(),
                'transaction_id' => 'TXN' . strtoupper(uniqid()),
                'is_paid' => rand(0, 1),
                'created_at' => $now->copy()->subMonths(2)->subDays(rand(0, 30)),
                'updated_at' => $now->copy()->subMonths(2)->subDays(rand(0, 30))
            ];
        }

        // إدراج الحجوزات
        DB::table('bookings')->insert($bookings);

        $this->command->info("✅ تم إنشاء " . count($bookings) . " حجز تجريبي بنجاح!");

        // عرض إحصائيات سريعة
        $totalBookings = DB::table('bookings')->count();
        $totalRevenue = DB::table('bookings')->sum('total');
        $paidBookings = DB::table('bookings')->where('is_paid', 1)->count();
        $pendingBookings = DB::table('bookings')->where('status', 'pending')->count();

        $this->command->info("📊 إحصائيات الحجوزات:");
        $this->command->table(
            ['المقياس', 'القيمة'],
            [
                ['إجمالي الحجوزات', $totalBookings],
                ['إجمالي الإيرادات', number_format($totalRevenue, 2) . ' ريال'],
                ['الحجوزات المدفوعة', $paidBookings],
                ['الحجوزات المعلقة', $pendingBookings]
            ]
        );
    }

    /**
     * الحصول على حالة عشوائية
     */
    protected function getRandomStatus(): string
    {
        $statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
        return $statuses[array_rand($statuses)];
    }

    /**
     * الحصول على طريقة دفع عشوائية
     */
    protected function getRandomPaymentMethod(): string
    {
        $methods = ['wallet', 'apple_pay', 'visa', 'mada', 'samsung_pay', 'benefit', 'stcpay'];
        return $methods[array_rand($methods)];
    }
}
