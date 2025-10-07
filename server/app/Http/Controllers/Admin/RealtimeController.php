<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\RealtimePresence;
use App\Events\AdminDashboardMetricsUpdated;
use Illuminate\Http\Request;

class RealtimeController extends Controller
{
    public function __construct(private readonly RealtimePresence $presence) {}

    // GET /admin/realtime/metrics
    public function metrics()
    {
        $counts = $this->presence->counts();
        return response()->json(['success' => true, 'data' => $counts]);
    }

    // POST /admin/realtime/heartbeat
    public function heartbeat(Request $request)
    {
        $user = auth('api')->user();
        if (!$user || $user->type !== 'admin') { return response()->json(['success' => false], 401); }

        [$counts, $changed] = $this->presence->heartbeat($user);
        if ($changed) {
            event(new AdminDashboardMetricsUpdated($counts));
        }
        return response()->json(['success' => true, 'data' => $counts]);
    }
}

