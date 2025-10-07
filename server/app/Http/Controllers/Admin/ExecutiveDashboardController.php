<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Booking;
use App\Models\SupportTicket;
use App\Models\Review;
use Illuminate\Support\Facades\DB;

class ExecutiveDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:api','user_type:admin','throttle:admin']);
    }

    // GET /dashboards/executive
    public function show()
    {
        abort_unless(auth('api')->user()?->can('dashboards.view'), 403);

        $usersGrowth = User::query()->selectRaw('DATE(created_at) as day, COUNT(*) as cnt')->groupBy('day')->orderBy('day','desc')->limit(30)->get();
        $revenue = DB::table('bookings')->selectRaw('SUM(total) as total')->value('total') ?? 0;
        $avgRating = DB::table('reviews')->selectRaw('AVG(rating) as avg')->value('avg') ?? 0;
        $openComplaints = SupportTicket::query()->whereIn('status', ['open','pending','in_progress'])->count();

        return format_response(true, 'OK', [
            'users_growth' => $usersGrowth,
            'revenue_total' => (float)$revenue,
            'avg_rating' => (float)$avgRating,
            'open_support_tickets' => (int)$openComplaints,
        ]);
    }
}

