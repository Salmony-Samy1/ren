<?php

namespace App\Http\Traits;

use Illuminate\Support\Facades\DB;

trait AnalyticsFormatterTrait
{
    /**
     * تنسيق بيانات الإحصائيات الأساسية لفعالية
     */
    protected function formatEventBasicStats($event): array
    {
        return [
            'event_id' => $event->id,
            'event_title' => $event->service->name ?? $event->event_name,
            'event_status' => $this->getEventStatus($event),
            
            // البيانات الأساسية
            'total_revenue' => floatval($event->total_revenue ?? 0),
            'tickets_available' => $this->calculateAvailableTickets($event),
            'tickets_booked' => $this->calculateBookedTickets($event), 
            'total_tickets' => $this->calculateTotalTickets($event),
            'sales_progress' => $this->calculateSalesProgress($event)
        ];
    }

    /**
     * تنسيق بيانات المبيعات اليومية
     */
    protected function formatDailySales($event): array
    {
        // يمكن استخدام cache للتطوير لاحقاً
        $dailySales = DB::table('tickets')
            ->join('bookings', 'tickets.booking_id', '=', 'bookings.id')
            ->where('tickets.event_id', $event->id)
            ->where('bookings.status', 'confirmed')
            ->select(DB::raw('DATE(tickets.created_at) as date'))
            ->selectRaw('COUNT(tickets.id) as tickets_sold')
            ->selectRaw('SUM(bookings.total) as revenue')
            ->groupBy(DB::raw('DATE(tickets.created_at)'))
            ->orderBy('date')
            ->get();

        return $dailySales->map(function ($sale) {
            return [
                'date' => $sale->date,
                'tickets_sold' => intval($sale->tickets_sold),
                'revenue' => floatval($sale->revenue)
            ];
        })->toArray();
    }

    /**
     * تنسيق بيانات توزيع التذاكر
     */
    protected function formatTicketDistribution($event): array
    {
        // هذا مثال - يجب تكييفه حسب البنية الفعلية للتذاكر
        $distribution = [];
        
        // استخدام بيانات Bookings بدلاً من التذاكر المباشرة
        $bookings = $event->bookings()->where('status', 'confirmed')->get();
        
        // توزيع بسيط حسب السعر
        $normalCount = $bookings->where('total', '<=', 150)->count();
        $vipCount = $bookings->where('total', '>', 150)->count();
        
        return [
            [
                'ticket_type' => 'تذكرة عادية',
                'sold' => $normalCount,
                'available' => max(0, $this->calculateTotalTickets($event) - $normalCount),
                'total' => $this->calculateTotalTickets($event),
                'revenue' => $bookings->where('total', '<=', 150)->sum('total')
            ],
            [
                'ticket_type' => 'تذكرة VIP',
                'sold' => $vipCount,
                'available' => max(0, $this->calculateTotalTickets($event) - $vipCount),
                'total' => $this->calculateTotalTickets($event),
                'revenue' => $bookings->where('total', '>', 150)->sum('total')
            ]
        ];
    }

    /**
     * حساب الوظائف المساعدة
     */
    private function getEventStatus($event): string
    {
        $now = now();
        $startDate = $event->start_at ? \Carbon\Carbon::parse($event->start_at) : null;
        $endDate = $event->end_at ? \Carbon\Carbon::parse($event->end_at) : null;
        
        if (!$startDate) return 'draft';
        if ($now->lt($startDate)) return 'upcoming';
        if ($endDate && $now->gt($endDate)) return 'finished';
        return 'ongoing';
    }

    private function calculateAvailableTickets($event): int
    {
        $bookedCount = $event->bookings()->where('status', 'confirmed')->count();
        return ($event->max_individuals ?? 0) - $bookedCount;
    }

    private function calculateBookedTickets($event): int
    {
        return $event->bookings()->where('status', 'confirmed')->count();
    }

    private function calculateTotalTickets($event): int
    {
        return $event->max_individuals ?? 0;
    }

    private function calculateSalesProgress($event): float
    {
        $total = $this->calculateTotalTickets($event);
        if ($total == 0) return 0.0;
        
        $sold = $this->calculateBookedTickets($event);
        return round(($sold / $total) * 100, 2);
    }
}
