<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BookingHub\BookingHubIndexRequest;
use App\Http\Requests\Admin\BookingHub\BookingHubUpdateStatusRequest;
use App\Models\Booking;
use App\Models\TableReservation;
use Illuminate\Http\Request;

class BookingHubController extends Controller
{
    public function index(BookingHubIndexRequest $request)
    {
        $this->authorize('viewAny', Booking::class); // admin via policy

        $types = $request->input('types') ?? ($request->filled('type') ? [$request->get('type')] : ['service','table']);
        $perPage = (int) $request->integer('per_page', 15);

        $filters = [
            'city_id' => $request->get('city_id'),
            'provider_id' => $request->get('provider_id'),
            'status' => $request->get('status'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'q' => $request->get('q'),
        ];

        $items = collect();

        if (in_array('service', $types)) {
            $q = Booking::query()
                ->with(['service:id,user_id,name,city_id','service.user:id,full_name,email','user:id,full_name,email'])
                ->orderByDesc('start_date');
            if ($filters['provider_id']) {
                $q->whereHas('service', fn($qq) => $qq->where('user_id', $filters['provider_id']));
            }
            if ($filters['city_id']) {
                $q->whereHas('service', fn($qq) => $qq->where('city_id', $filters['city_id']));
            }
            if ($filters['status']) {
                $q->where('status', $filters['status']);
            }
            if ($filters['date_from']) { $q->whereDate('start_date', '>=', $filters['date_from']); }
            if ($filters['date_to']) { $q->whereDate('end_date', '<=', $filters['date_to']); }
            if ($filters['q']) { $q->where('reference_code', 'like', '%'.$filters['q'].'%'); }

            $svc = $q->paginate($perPage);
            $items = $items->merge($svc->items());
        }

        if (in_array('table', $types)) {
            $qt = TableReservation::query()
                ->with(['table:id,restaurant_id,name,capacity_people', 'table.restaurant:id,service_id', 'table.restaurant.service:id,user_id,name,city_id'])
                ->orderByDesc('start_time');
            if ($filters['provider_id']) {
                $qt->whereHas('table.restaurant.service', fn($qq) => $qq->where('user_id', $filters['provider_id']));
            }
            if ($filters['city_id']) {
                $qt->whereHas('table.restaurant.service', fn($qq) => $qq->where('city_id', $filters['city_id']));
            }
            if ($filters['status']) { $qt->where('status', $filters['status']); }
            if ($filters['date_from']) { $qt->whereDate('start_time', '>=', $filters['date_from']); }
            if ($filters['date_to']) { $qt->whereDate('end_time', '<=', $filters['date_to']); }
            if ($filters['q']) { $qt->where('notes', 'like', '%'.$filters['q'].'%'); }

            $tab = $qt->paginate($perPage);
            $items = $items->merge($tab->items());
        }

        // Note: when aggregating multiple paginated sources, we return items merged with simple meta
        $items = $items->sortByDesc(function($i){
            return isset($i->start_date) ? $i->start_date : ($i->start_time ?? now());
        })->values();

        return format_response(true, __('OK'), [
            'items' => $items,
            'meta' => [
                'per_page' => $perPage,
                'count' => $items->count(),
                'types' => $types,
            ]
        ]);
    }

    public function updateStatus($type, $id, BookingHubUpdateStatusRequest $request)
    {
        $payload = $request->validated();
        $status = $payload['status'];

        switch ($type) {
            case 'service':
                $booking = Booking::findOrFail($id);
                $this->authorize('update', $booking);
                $booking->update(['status' => $status]);
                return format_response(true, __('Updated'), $booking->fresh());
            case 'table':
                $reservation = TableReservation::findOrFail($id);
                $this->authorize('update', $reservation);
                $reservation->update(['status' => $status]);
                return format_response(true, __('Updated'), $reservation->fresh());
            case 'event':
                // Optional: if we support event booking/tickets status updates centrally
                return format_response(false, __('Not implemented for events'), null, 400);
            default:
                return format_response(false, __('Invalid type'), null, 422);
        }
    }
}

