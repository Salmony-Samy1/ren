<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewsController extends Controller
{
    /**
     * عرض جميع التقييمات
     */
    public function index(Request $request)
    {
        $query = Review::with(['user:id,name,email', 'service:id,name', 'booking:id'])
            ->orderBy('created_at', 'desc');

        // فلترة حسب الحالة
        if ($request->has('status')) {
            if ($request->status === 'approved') {
                $query->approved();
            } elseif ($request->status === 'pending') {
                $query->pending();
            }
        }

        // فلترة حسب التقييم
        if ($request->has('rating')) {
            $query->where('rating', $request->rating);
        }

        $reviews = $query->paginate(20);

        return format_response(true, 'تم جلب التقييمات بنجاح', $reviews);
    }

    /**
     * عرض تقييم محدد
     */
    public function show(Review $review)
    {
        $review->load(['user:id,name,email', 'service:id,name', 'booking:id']);
        
        return format_response(true, 'تم جلب التقييم بنجاح', $review);
    }

    /**
     * الموافقة على تقييم
     */
    public function approve(Review $review)
    {
        $review->update([
            'is_approved' => true,
            'approved_at' => now(),
        ]);

        return format_response(true, 'تم الموافقة على التقييم بنجاح');
    }

    /**
     * رفض تقييم
     */
    public function reject(Request $request, Review $review)
    {
        $request->validate([
            'notes' => 'required|string|max:500'
        ]);

        $review->update([
            'is_approved' => false,
            'approval_notes' => $request->notes,
        ]);

        return format_response(true, 'تم رفض التقييم بنجاح');
    }

    /**
     * حذف تقييم
     */
    public function destroy(Review $review)
    {
        $review->delete();

        return format_response(true, 'تم حذف التقييم بنجاح');
    }

    /**
     * إحصائيات التقييمات
     */
    public function statistics()
    {
        $stats = [
            'total_reviews' => Review::count(),
            'approved_reviews' => Review::approved()->count(),
            'pending_reviews' => Review::pending()->count(),
            'average_rating' => Review::approved()->avg('rating') ?? 0,
            'reviews_by_rating' => [
                '5_stars' => Review::approved()->where('rating', 5)->count(),
                '4_stars' => Review::approved()->where('rating', 4)->count(),
                '3_stars' => Review::approved()->where('rating', 3)->count(),
                '2_stars' => Review::approved()->where('rating', 2)->count(),
                '1_star' => Review::approved()->where('rating', 1)->count(),
            ],
            'recent_reviews' => Review::with(['user:id,name', 'service:id,name'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
        ];

        return format_response(true, 'تم جلب الإحصائيات بنجاح', $stats);
    }
}
