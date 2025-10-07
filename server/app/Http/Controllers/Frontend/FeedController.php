<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Booking;
use App\Models\Follow;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();


        // Get accepted followings (users the current user follows), including self
        $followedIds = Follow::where('follower_id', $userId)
            ->where('status', 'accepted')
            ->pluck('user_id');
        $followedIds = $followedIds->push($userId)->unique();

        // Collect recent public activities across services
        $reviews = Review::with(['service:id,name', 'user:id,name'])
            ->whereIn('user_id', $followedIds)
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn($r) => [
                'type' => 'review_added',
                'user' => ['id' => $r->user_id, 'name' => $r->user->name ?? null],
                'service' => ['id' => $r->service_id, 'name' => $r->service->name ?? null],
                'rating' => $r->rating,
                'comment' => $r->comment,
                'created_at' => $r->created_at,
            ]);

        // Show bookings when status is confirmed or completed
        $bookings = Booking::with(['service:id,name'])
            ->whereIn('user_id', $followedIds)
            ->whereIn('status', ['confirmed','completed'])
            ->where(function ($q) use ($userId) {
                $q->where('privacy', 'public')
                  ->orWhere(function ($q2) use ($userId) {
                      $q2->where('privacy', 'custom')
                         ->whereExists(function ($sub) use ($userId) {
                             $sub->select(\DB::raw(1))
                                 ->from('booking_privacy_users')
                                 ->whereColumn('booking_privacy_users.booking_id', 'bookings.id')
                                 ->where('booking_privacy_users.viewer_user_id', $userId);
                         });
                  });
            })
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn($b) => [
                'type' => $b->status === 'completed' ? 'booking_completed' : 'booking_confirmed',
                'service' => ['id' => $b->service_id, 'name' => $b->service->name ?? null],
                'created_at' => $b->updated_at ?? $b->created_at,
            ]);

        $items = $reviews->merge($bookings)->sortByDesc('created_at')->values();

        return format_response(true, __('api.feed.title'), [
            'data' => $items->take(50),
        ]);
    }

    public function publicFeed()
    {
        $bookings = Booking::with(['service:id,name'])
            ->where('status', 'completed')
            ->where('privacy', 'public')
            ->latest()->limit(20)->get()
            ->map(fn($b) => [
                'type' => 'booking_completed',
                'service' => ['id' => $b->service_id, 'name' => $b->service->name ?? null],
                'created_at' => $b->updated_at ?? $b->created_at,
            ]);
        return format_response(true, __('api.feed.title'), ['data' => $bookings]);
    }

}

