<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Service;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GeoController extends Controller
{
    public function __construct()
    {
        $this->middleware(['force.guard.api','auth:api','user_type:admin','throttle:admin']);
    }

    private function tokenHas(string $permission): bool
    {
        try {
            $payload = \Tymon\JWTAuth\Facades\JWTAuth::parseToken()->getPayload();
            $perms = (array) ($payload->get('permissions') ?? []);
            return in_array($permission, $perms, true);
        } catch (\Throwable $e) {
            return false;
        }
    }


    // PATCH /geo/cities/{city}/status
    public function updateCityStatus(City $city, Request $request)
    {
        abort_unless($this->tokenHas('geo.manage') || auth('api')->user()?->can('geo.manage'), 403);

        $v = Validator::make($request->all(), ['status' => 'required|boolean']);
        if ($v->fails()) { return format_response(false, 'Invalid', $v->errors(), 422); }
        $city->update(['is_active' => (bool)$request->boolean('status')]);
        // Optionally disable services in that city
        Service::whereHas('property', fn($q)=>$q->where('city_id', $city->id))
            ->update(['is_approved' => (bool)$request->boolean('status')]);
        return format_response(true, __('Updated'), ['city_id' => $city->id, 'is_active' => $city->is_active]);
    }

    // GET /geo/markets/performance
    public function marketsPerformance(Request $request)
    {
        abort_unless($this->tokenHas('geo.view') || auth('api')->user()?->can('geo.view'), 403);

        $v = Validator::make($request->all(), [
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);
        if ($v->fails()) { return format_response(false, 'Invalid', $v->errors(), 422); }
        $from = $request->date('date_from') ?: now()->subDays(30);
        $to = $request->date('date_to') ?: now();

        $stats = DB::table('services as s')
            ->join('properties as p', 'p.service_id', '=', 's.id')
            ->leftJoin('bookings as b', function($join) use ($from, $to) {
                $join->on('b.service_id','=','s.id')
                    ->whereBetween('b.start_date', [$from, $to]);
            })
            ->selectRaw('p.city_id, COUNT(DISTINCT s.id) as services, COUNT(DISTINCT b.id) as bookings, COALESCE(SUM(b.total),0) as revenue')
            ->groupBy('p.city_id')
            ->orderByDesc('revenue')
            ->get();

        return format_response(true, 'OK', ['markets' => $stats]);
    }

    // GET /geo/markets/demand-forecast
    public function demandForecast(Request $request)
    {
        abort_unless($this->tokenHas('geo.view') || auth('api')->user()?->can('geo.view'), 403);

        $windowDays = (int)($request->integer('window_days') ?: 30);
        $rows = DB::table('bookings as b')
            ->join('services as s','s.id','=','b.service_id')
            ->join('properties as p','p.service_id','=','s.id')
            ->selectRaw('p.city_id, DATE(b.start_date) as day, COUNT(b.id) as cnt')
            ->where('b.start_date','>=', now()->subDays($windowDays))
            ->groupBy('p.city_id','day')
            ->orderBy('day')
            ->get();
        // Simple moving average by city
        $forecast = collect($rows)->groupBy('city_id')->map(function($g){
            $series = $g->pluck('cnt')->values();
            $avg = $series->avg();
            return [
                'current_avg' => (float)$avg,
                'next_7days_forecast' => array_fill(0, 7, (float) round($avg,2)),
            ];
        });
        return format_response(true, 'OK', ['forecast' => $forecast]);
    }
}

