<?php

namespace App\Http\Controllers\Admin\Providers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Booking;
use App\Models\Review;
use App\Models\Invoice;
use App\Models\CompanyLegalDocument;
use App\Models\Alert;
use App\Repositories\UserRepo\IUserRepo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProvidersController extends Controller
{
    public function __construct(private readonly IUserRepo $userRepo)
    {
    }

    /**
     * Display a listing of providers
     */
    public function index(Request $request)
    {
        $filters = array_merge(['type' => 'provider'], $request->only(['status', 'is_approved']));
        $providers = $this->userRepo->getAll(
            paginated: true, 
            filter: $filters, 
            withTrashed: $request->boolean('with_trashed')
        );
        
        if ($providers) {
            return new UserCollection($providers);
        }
        
        return format_response(false, __('Something Went Wrong'), code: 500);
    }

    /**
     * Display the specified provider
     */
    public function show(User $provider)
    {
        if ($provider->type !== 'provider') {
            return format_response(false, __('Invalid provider'), code: 422);
        }
        
        $provider->load([
            'companyProfile.city',
            'companyProfile.mainService',
            'companyProfile.legalDocuments.mainService',
            'companyProfile.services.category',
            'companyProfile.activities',
            'companyProfile.properties',
            'companyProfile.restaurants'
        ]);
        
        return format_response(true, __('Fetched successfully'), new UserResource($provider));
    }

    /**
     * Update provider status
     */
    public function updateStatus(UpdateUserStatusRequest $request, User $provider)
    {
        if ($provider->type !== 'provider') {
            return format_response(false, __('Invalid provider'), code: 422);
        }
        
        $this->authorize('updateStatus', $provider);
        $provider->update(['status' => $request->validated()['status']]);
        
        return format_response(
            true, 
            __('Provider status updated successfully'), 
            new UserResource($provider->refresh())
        );
    }

    /**
     * Approve provider
     */
    public function approve(User $provider)
    {
        if ($provider->type !== 'provider') {
            return format_response(false, __('Invalid provider'), code: 422);
        }
        
        $this->authorize('approve', $provider);
        $provider->update([
            'is_approved' => true,
            'approved_at' => now()
        ]);
        
        return format_response(
            true, 
            __('Provider approved successfully'), 
            new UserResource($provider->refresh())
        );
    }

    /**
     * Reject provider
     */
    public function reject(User $provider)
    {
        if ($provider->type !== 'provider') {
            return format_response(false, __('Invalid provider'), code: 422);
        }
        
        $this->authorize('reject', $provider);
        $provider->update([
            'is_approved' => false,
            'approved_at' => null
        ]);
        
        return format_response(
            true, 
            __('Provider rejected'), 
            new UserResource($provider->refresh())
        );
    }

    /**
     * Get provider performance data
     */
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

    /**
     * Get provider reviews
     */
    public function reviews(User $provider, Request $request)
    {
        if ($provider->type !== 'provider') {
            return format_response(false, __('Invalid provider'), code: 422);
        }

        $query = Review::with(['user:id,name,email', 'service:id,name', 'booking:id,reference_code'])
            ->whereHas('service', function ($q) use ($provider) {
                $q->where('user_id', $provider->id);
            })
            ->when($request->rating, function ($q) use ($request) {
                $q->where('rating', $request->rating);
            })
            ->when($request->status, function ($q) use ($request) {
                $q->where('is_approved', $request->status === 'approved');
            })
            ->when($request->date_from, function ($q) use ($request) {
                $q->whereDate('created_at', '>=', $request->date_from);
            })
            ->when($request->date_to, function ($q) use ($request) {
                $q->whereDate('created_at', '<=', $request->date_to);
            })
            ->orderBy('created_at', 'desc');

        $reviews = $query->paginate(15);

        return format_response(true, __('Fetched successfully'), [
            'data' => $reviews->items(),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ]
        ]);
    }

    /**
     * Approve review
     */
    public function approveReview(Review $review)
    {
        $review->update([
            'is_approved' => true,
            'approved_at' => now()
        ]);
        
        return format_response(true, __('Review approved successfully'));
    }

    /**
     * Reject review
     */
    public function rejectReview(Review $review)
    {
        $review->update([
            'is_approved' => false,
            'approved_at' => null
        ]);
        
        return format_response(true, __('Review rejected'));
    }

    /**
     * Get provider documents
     */
    public function documents(User $provider, Request $request)
    {
        if ($provider->type !== 'provider' || !$provider->companyProfile) {
            return format_response(false, __('Invalid provider or no company profile'), code: 422);
        }

        $query = $provider->companyProfile->legalDocuments()
            ->with(['mainService'])
            ->when($request->status, function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when($request->expired, function ($q) {
                $q->where('expires_at', '<', now());
            })
            ->when($request->expiring_soon, function ($q) {
                $q->where('expires_at', '>=', now())
                  ->where('expires_at', '<=', now()->addDays(30));
            })
            ->orderBy('created_at', 'desc');

        $documents = $query->paginate(15);

        return format_response(true, __('Fetched successfully'), [
            'data' => $documents->items(),
            'meta' => [
                'current_page' => $documents->currentPage(),
                'per_page' => $documents->perPage(),
                'total' => $documents->total(),
            ]
        ]);
    }

    /**
     * Get provider alerts
     */
    public function alerts(User $provider, Request $request)
    {
        if ($provider->type !== 'provider') {
            return format_response(false, __('Invalid provider'), code: 422);
        }

        $query = Alert::where('user_id', $provider->id)
            ->when($request->type, function ($q) use ($request) {
                $q->where('type', $request->type);
            })
            ->when($request->severity, function ($q) use ($request) {
                $q->where('severity', $request->severity);
            })
            ->when($request->status, function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->orderBy('created_at', 'desc');

        $alerts = $query->paginate(15);

        return format_response(true, __('Fetched successfully'), [
            'data' => $alerts->items(),
            'meta' => [
                'current_page' => $alerts->currentPage(),
                'per_page' => $alerts->perPage(),
                'total' => $alerts->total(),
            ]
        ]);
    }

    /**
     * Mark alert as read
     */
    public function markAlertAsRead(Alert $alert)
    {
        $alert->markAsRead();
        return format_response(true, __('Alert marked as read'));
    }

    /**
     * Acknowledge alert
     */
    public function acknowledgeAlert(Alert $alert)
    {
        $alert->acknowledge(auth()->user());
        return format_response(true, __('Alert acknowledged'));
    }

    /**
     * Resolve alert
     */
    public function resolveAlert(Alert $alert)
    {
        $alert->resolve();
        return format_response(true, __('Alert resolved'));
    }

    /**
     * Get provider comparison data
     */
    public function comparison(Request $request)
    {
        $from = $request->date('from')?->startOfDay() ?? Carbon::now()->startOfMonth();
        $to = $request->date('to')?->endOfDay() ?? Carbon::now()->endOfMonth();
        $cityId = $request->city_id;
        $serviceId = $request->main_service_id;

        $query = User::with(['companyProfile.city', 'companyProfile.mainService'])
            ->where('type', 'provider')
            ->whereHas('companyProfile', function ($q) use ($cityId, $serviceId) {
                $q->when($cityId, function ($cityQuery) use ($cityId) {
                    $cityQuery->where('city_id', $cityId);
                })
                ->when($serviceId, function ($serviceQuery) use ($serviceId) {
                    $serviceQuery->where('main_service_id', $serviceId);
                });
            });

        $providers = $query->get();

        $comparisonData = $providers->map(function ($provider) use ($from, $to) {
            return $this->calculateProviderMetrics($provider, $from, $to);
        });

        return format_response(true, __('Fetched successfully'), [
            'data' => $comparisonData,
            'meta' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'total_providers' => $providers->count(),
            ]
        ]);
    }

    /**
     * Calculate provider metrics for comparison
     */
    private function calculateProviderMetrics($provider, $startDate, $endDate)
    {
        // Get bookings data
        $bookingsQuery = Booking::whereHas('service', function ($query) use ($provider) {
            $query->where('user_id', $provider->id);
        })->whereBetween('created_at', [$startDate, $endDate]);

        $bookingsData = $bookingsQuery->selectRaw('
            COUNT(*) as total_bookings,
            SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_bookings,
            SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled_bookings,
            SUM(total) as total_revenue,
            AVG(total) as avg_booking_value
        ')->first();

        // Get reviews data
        $reviewsQuery = Review::whereHas('service', function ($query) use ($provider) {
            $query->where('user_id', $provider->id);
        })->whereBetween('created_at', [$startDate, $endDate]);

        $reviewsData = $reviewsQuery->selectRaw('
            COUNT(*) as total_reviews,
            AVG(rating) as avg_rating,
            SUM(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) as positive_reviews
        ')->first();

        // Calculate rates
        $completionRate = $bookingsData->total_bookings > 0 
            ? ($bookingsData->completed_bookings / $bookingsData->total_bookings) * 100 
            : 0;

        $satisfactionRate = $reviewsData->total_reviews > 0 
            ? ($reviewsData->positive_reviews / $reviewsData->total_reviews) * 100 
            : 0;

        return [
            'provider_id' => $provider->id,
            'provider_name' => $provider->full_name ?? $provider->name,
            'provider_email' => $provider->email,
            'company_name' => $provider->companyProfile?->company_name ?? 'غير محدد',
            'city_name' => $provider->companyProfile?->city?->name ?? 'غير محدد',
            'main_service_name' => $provider->companyProfile?->mainService?->name ?? 'غير محدد',
            'status' => $provider->status,
            'is_approved' => $provider->is_approved,
            'total_bookings' => $bookingsData->total_bookings ?? 0,
            'completed_bookings' => $bookingsData->completed_bookings ?? 0,
            'cancelled_bookings' => $bookingsData->cancelled_bookings ?? 0,
            'completion_rate' => round($completionRate, 2),
            'revenue' => $bookingsData->total_revenue ?? 0,
            'avg_booking_value' => $bookingsData->avg_booking_value ?? 0,
            'total_reviews' => $reviewsData->total_reviews ?? 0,
            'avg_rating' => round($reviewsData->avg_rating ?? 0, 2),
            'satisfaction_rate' => round($satisfactionRate, 2),
        ];
    }
}

