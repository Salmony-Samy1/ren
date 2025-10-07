<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Repositories\UserRepo\IUserRepo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProvidersController extends Controller
{
    public function __construct(private readonly IUserRepo $userRepo)
    {
    }

    public function index()
    {
        $providers = $this->userRepo->getAll(paginated: true, filter: ['type' => 'provider'], withTrashed: true);
        if ($providers) {
            return new UserCollection($providers);
        }
        return format_response(false, __('Something Went Wrong'), code: 500);
    }

    public function show(User $provider)
    {
        return format_response(true, __('Fetched successfully'), new UserResource($provider));
    }

    public function updateStatus(UpdateUserStatusRequest $request, User $provider)
    {
        // Providers are stored in users table with type='provider'
        if ($provider->type !== 'provider') {
            return format_response(false, __('Invalid provider'), code: 422);
        }
        $this->authorize('updateStatus', $provider);
        $provider->update(['status' => $request->validated()['status']]);
        return format_response(true, __('Provider status updated successfully'), new UserResource($provider->refresh()));
    }

    public function performance(Request $request)
    {
        $from = $request->date('from')?->startOfDay() ?? Carbon::now()->subDays(30)->startOfDay();
        $to = $request->date('to')?->endOfDay() ?? Carbon::now()->endOfDay();
        $providerId = $request->integer('provider_id');

        $query = DB::table('bookings')
            ->join('services', 'bookings.service_id', '=', 'services.id')
            ->join('users', 'services.user_id', '=', 'users.id')
            ->leftJoin('reviews', 'reviews.booking_id', '=', 'bookings.id')
            ->whereBetween('bookings.created_at', [$from, $to])
            ->when($providerId, fn($q) => $q->where('services.user_id', $providerId))
            ->groupBy('services.user_id')
            ->select([
                'services.user_id as provider_id',
                DB::raw('MIN(users.email) as provider_email'),
                DB::raw('COUNT(DISTINCT bookings.id) as total_bookings'),
                DB::raw('COALESCE(SUM(bookings.total),0) as total_revenue'),
                DB::raw('AVG(reviews.rating) as avg_rating'),
                DB::raw("SUM(CASE WHEN bookings.status = 'completed' THEN 1 ELSE 0 END) as completed"),
                DB::raw("SUM(CASE WHEN bookings.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled"),
            ]);

        $rows = $query->paginate(15);

        $data = collect($rows->items())->map(function ($row) {
            $total = (int) $row->total_bookings;
            $completed = (int) $row->completed;
            $cancelled = (int) $row->cancelled;
            return [
                'provider_id' => (int) $row->provider_id,
                'provider_name' => null,
                'provider_email' => $row->provider_email,
                'total_bookings' => $total,
                'total_revenue' => (float) $row->total_revenue,
                'avg_rating' => $row->avg_rating !== null ? round((float) $row->avg_rating, 2) : null,
                'completion_rate' => $total > 0 ? round($completed / $total, 4) : 0.0,
                'cancellation_rate' => $total > 0 ? round($cancelled / $total, 4) : 0.0,
            ];
        });

        return format_response(true, __('Fetched successfully'), [
            'data' => $data,
            'meta' => [
                'current_page' => $rows->currentPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
        ]);
    }
}
