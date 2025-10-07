<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\User;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    public function topProviders(Request $request)
    {
        $limit = min((int)$request->query('limit', 10), 50);
        $topProvidersStats = User::select('users.id', DB::raw('COUNT(b.id) as completed_bookings'))
            ->join('services as s', 's.user_id', '=', 'users.id')
            ->join('bookings as b', 'b.service_id', '=', 's.id')
            ->where('users.type', 'provider')
            ->whereIn('b.status', ['confirmed', 'completed'])
            ->groupBy('users.id')
            ->orderByDesc('completed_bookings')
            ->limit($limit)
            ->get();
        if ($topProvidersStats->isEmpty()) {
            return format_response(true, 'Fetched', []);
        }
        $providerIds = $topProvidersStats->pluck('id');
        $users = User::with('media')
            ->whereIn('id', $providerIds)
            ->get()
            ->keyBy('id');
        $results = $topProvidersStats->map(function ($stat) use ($users) {
            $user = $users->get($stat->id);
            if (!$user) {
                return null;
            }

            return [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'avatar_url' => $user->getFirstMediaUrl('avatar'),
                'completed_bookings' => (int) $stat->completed_bookings,
            ];
        })->filter(); 

        return format_response(true, 'Fetched', $results);
    }

    // Public: top catering services by orders count (bookings)
    public function topCatering(Request $request)
    {
        $limit = min((int)$request->query('limit', 10), 50);
        $period = $request->query('period'); // e.g., last_30_days
        $from = $request->query('from');
        $to = $request->query('to');
        $cityId = $request->query('city_id');

        // First, compute top service IDs by orders count within the filters
        $ranked = Service::select('services.id', DB::raw('COUNT(b.id) as orders'))
            ->join('caterings as c', 'c.service_id', '=', 'services.id')
            ->leftJoin('bookings as b', 'b.service_id', '=', 'services.id')
            ->when($cityId, fn($q) => $q->where('services.city_id', (int)$cityId))
            ->when($period === 'last_30_days', fn($q) => $q->where('b.created_at', '>=', now()->subDays(30)))
            ->when($from && $to, fn($q) => $q->whereBetween('b.created_at', [$from, $to]))
            ->whereNull('services.deleted_at')
            ->where(function($q){ $q->whereNull('b.status')->orWhereIn('b.status', ['confirmed','completed']); })
            ->groupBy('services.id')
            ->orderByDesc('orders')
            ->limit($limit)
            ->get();

        $serviceIds = $ranked->pluck('id')->all();

        // Load full service details for those IDs
        $services = Service::with([
            'category',
            'user.companyProfile',
            'catering.items',
            'favorites.user',
            'bookings.user',
            'reviews',
        ])->whereIn('id', $serviceIds)->get()->keyBy('id');

        // Build payload: full service resource + orders count
        $data = $ranked->map(function ($row) use ($services, $request) {
            $service = $services->get($row->id);
            return [
                'orders' => (int) $row->orders,
                'service' => $service ? new \App\Http\Resources\ServiceResource($service) : null,
            ];
        })->values();

        return format_response(true, 'Fetched', $data);
    }
}

