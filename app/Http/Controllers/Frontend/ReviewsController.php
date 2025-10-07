<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewRequest;
use App\Models\Review;
use App\Models\Service;
use App\Models\Booking;
use App\Services\PointsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;


class ReviewsController extends Controller
{
    public function __construct(private readonly PointsService $pointsService)
    {
    }

    public function index(Service $service)
    {
        $reviews = $service->reviews()
            ->with('user:id,full_name')
            ->approved()
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $reviews
        ]);
    }

    public function store(ReviewRequest $request, Service $service)
    {
        $user = auth()->user();
        $booking = Booking::where('user_id', $user->id)
                    ->where('service_id', $service->id)
                    ->wherePast('end_date')
                    ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'يجب أن تكون قد حجزت هذه الخدمة وأكملتها لتتمكن من تقييمها'
            ], 403);
        }

        $existingReview = Review::where('user_id', $user->id)
            ->where('service_id', $service->id)
            ->first();

        if ($existingReview) {
            return response()->json([
                'success' => false,
                'message' => 'لقد قمت بتقييم هذه الخدمة مسبقاً'
            ], 400);
        }

        // إنشاء التقييم
        $review = Review::create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'booking_id' => $booking->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'is_approved' => true,
            'approved_at' => now(),
        ]);

        $this->pointsService->awardReviewPoints($review);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة تقييمك بنجاح',
            'data' => $review->load('user:id,full_name')
        ], 201);
    }

    /**
     * عرض تقييم محدد
     */
    public function show(Review $review)
    {
        $this->authorize('view', $review);
        return response()->json([
            'success' => true,
            'data' => $review->load('user:id,name', 'service:id,name')
        ]);
    }

    /**
     * تحديث تقييم
     */
    public function update(ReviewRequest $request, Review $review)
    {
        $this->authorize('update', $review);

        $review->update([
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث تقييمك بنجاح',
            'data' => $review->load('user:id,full_name')
        ]);
    }

    /**
     * حذف تقييم
     */
    public function destroy(Review $review)
    {
        $this->authorize('delete', $review);

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف تقييمك بنجاح'
        ]);
    }

    /**
     * عرض تقييمات المستخدم
     */
    public function myReviews(Request $request)
    {
        $user = auth()->user();
        
        $reviews = $user->reviews()
            ->with('service:id,name,category_id', 'service.category:id')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $reviews
        ]);
    }
}
