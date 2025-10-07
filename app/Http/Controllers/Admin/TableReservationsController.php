<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Reservations\TableReservationStoreRequest;
use App\Http\Requests\Admin\Reservations\TableReservationUpdateStatusRequest;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\TableReservation;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TableReservationsController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', TableReservation::class);

        $q = TableReservation::query()->with(['table:id,restaurant_id,name,capacity_people', 'user:id,full_name,email']);
        if ($request->filled('restaurant_id')) {
            $restaurantId = (int) $request->integer('restaurant_id');
            $q->whereHas('table', fn($qq) => $qq->where('restaurant_id', $restaurantId));
        }
        if ($request->filled('status')) {
            $q->where('status', $request->get('status'));
        }
        if ($request->filled('date_from')) {
            $q->whereDate('start_time', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $q->whereDate('end_time', '<=', $request->get('date_to'));
        }
        $items = $q->orderByDesc('start_time')->paginate($request->integer('per_page', 15));
        return format_response(true, __('OK'), [
            'items' => $items->items(),
            'meta' => $items->toArray()['meta'] ?? [
                'current_page' => $items->currentPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
            ]
        ]);
    }

    public function store(TableReservationStoreRequest $request)
    {
        $this->authorize('create', TableReservation::class);
        $data = $request->validated();

        // Optional: prevent overlapping reservations on same table
        $overlap = TableReservation::query()
            ->where('restaurant_table_id', $data['restaurant_table_id'])
            ->where(function($q) use ($data){
                $q->whereBetween('start_time', [$data['start_time'], $data['end_time']])
                  ->orWhereBetween('end_time', [$data['start_time'], $data['end_time']])
                  ->orWhere(function($qq) use ($data){
                      $qq->where('start_time', '<=', $data['start_time'])
                         ->where('end_time', '>=', $data['end_time']);
                  });
            })
            ->exists();
        if ($overlap) {
            return format_response(false, __('Table already reserved in selected period'), null, 422);
        }

        $reservation = TableReservation::create($data);
        return format_response(true, __('Created'), $reservation);
    }

    public function updateStatus(TableReservation $reservation, TableReservationUpdateStatusRequest $request)
    {
        $this->authorize('update', $reservation);
        $reservation->update(['status' => $request->validated()['status']]);
        return format_response(true, __('Updated'), $reservation->fresh());
    }

    public function calendar(Request $request)
    {
        $this->authorize('viewAny', TableReservation::class);
        $request->validate([
            'restaurant_id' => ['required','integer','exists:restaurants,id'],
            'range' => ['nullable','in:day,week,month'],
            'date' => ['nullable','date'],
        ]);
        $restaurant = Restaurant::with(['tables:id,restaurant_id,name,capacity_people'])->findOrFail($request->integer('restaurant_id'));
        $range = $request->get('range', 'day');
        $date = $request->filled('date') ? Carbon::parse($request->get('date')) : now();

        // Define period
        switch ($range) {
            case 'week':
                $start = $date->copy()->startOfWeek();
                $end = $date->copy()->endOfWeek();
                break;
            case 'month':
                $start = $date->copy()->startOfMonth();
                $end = $date->copy()->endOfMonth();
                break;
            default:
                $start = $date->copy()->startOfDay();
                $end = $date->copy()->endOfDay();
        }

        $reservations = TableReservation::query()
            ->whereHas('table', fn($q) => $q->where('restaurant_id', $restaurant->id))
            ->whereBetween('start_time', [$start, $end])
            ->with('table:id,restaurant_id,name,capacity_people')
            ->get();

        $calendar = $restaurant->tables->map(function($table) use ($reservations, $start, $end){
            $period = CarbonPeriod::create($start, '1 hour', $end);
            $slots = collect($period)->map(function($dt) use ($reservations, $table){
                $slotStart = $dt->copy();
                $slotEnd = $dt->copy()->addHour();
                $isBooked = $reservations->first(function($r) use ($table, $slotStart, $slotEnd){
                    return $r->restaurant_table_id === $table->id && (
                        ($r->start_time <= $slotStart && $r->end_time > $slotStart) ||
                        ($r->start_time < $slotEnd && $r->end_time >= $slotEnd) ||
                        ($r->start_time >= $slotStart && $r->end_time <= $slotEnd)
                    );
                });
                return [
                    'start' => $slotStart->toDateTimeString(),
                    'end' => $slotEnd->toDateTimeString(),
                    'status' => $isBooked ? 'booked' : 'available',
                ];
            });
            return [
                'table' => [
                    'id' => $table->id,
                    'name' => $table->name,
                    'capacity_people' => $table->capacity_people,
                ],
                'slots' => $slots,
            ];
        })->values();

        return format_response(true, __('OK'), [
            'restaurant' => [ 'id' => $restaurant->id, 'name' => $restaurant->name ?? null ],
            'range' => $range,
            'from' => $start->toDateTimeString(),
            'to' => $end->toDateTimeString(),
            'tables' => $calendar,
        ]);
    }

    public function storeOperatingHours(Restaurant $venue, Request $request)
    {
        $this->authorize('update', $venue);
        $data = $request->validate([
            'working_hours' => ['required','array'],
            'working_hours.*.day_of_week' => ['required','integer','between:0,6'],
            'working_hours.*.open_time' => ['required','date_format:H:i'],
            'working_hours.*.close_time' => ['required','date_format:H:i','different:working_hours.*.open_time'],
            'working_hours.*.is_peak' => ['nullable','boolean'],
        ]);

        $venue->update([
            'working_hours' => $data['working_hours'],
        ]);

        return format_response(true, __('Updated'), $venue->fresh());
    }
}

