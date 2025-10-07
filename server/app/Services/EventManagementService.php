<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Service;
use App\Models\User;
use App\Models\Booking;
use App\Models\Order;
use App\Models\Review;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

class EventManagementService
{
    /**
     * Create a new event with all necessary data
     *
     * @param array $data
     * @param User $organizer
     * @return Event
     */
    public function createEvent(array $data, User $organizer): Event
    {
        return DB::transaction(function () use ($data, $organizer) {
            // Create service first
            $service = Service::create([
                'user_id' => $organizer->id,
                'name' => $data['event_name'],
                'description' => $data['description'] ?? '',
                'price' => $data['base_price'] ?? 0,
                'currency' => $data['currency'] ?? 'SAR',
                'is_approved' => true, // Admin created events are auto-approved
                'status' => 'active',
            ]);

            // Create event
            $event = Event::create([
                'service_id' => $service->id,
                'event_name' => $data['event_name'],
                'description' => $data['description'] ?? '',
                'max_individuals' => $data['max_individuals'] ?? 100,
                'gender_type' => $data['gender_type'] ?? 'both',
                'hospitality_available' => $data['hospitality_available'] ?? false,
                'pricing_type' => $data['pricing_type'] ?? 'fixed',
                'base_price' => $data['base_price'] ?? 0,
                'discount_price' => $data['discount_price'] ?? null,
                'prices_by_age' => $data['prices_by_age'] ?? null,
                'cancellation_policy' => $data['cancellation_policy'] ?? 'standard',
                'meeting_point' => $data['meeting_point'] ?? '',
                'start_at' => $data['start_at'] ?? now()->addDays(7),
                'end_at' => $data['end_at'] ?? now()->addDays(7)->addHours(4),
                'age_min' => $data['age_min'] ?? 0,
                'age_max' => $data['age_max'] ?? 100,
                'price_currency_id' => $data['price_currency_id'] ?? 1,
                'price_per_person' => $data['price_per_person'] ?? $data['base_price'] ?? 0,
            ]);

            return $event->load(['service.user', 'service']);
        });
    }

    /**
     * Update an existing event
     *
     * @param Event $event
     * @param array $data
     * @return Event
     */
    public function updateEvent(Event $event, array $data): Event
    {
        return DB::transaction(function () use ($event, $data) {
            // Update service
            $event->service->update([
                'name' => $data['event_name'] ?? $event->service->name,
                'description' => $data['description'] ?? $event->service->description,
                'price' => $data['base_price'] ?? $event->service->price,
            ]);

            // Update event
            $event->update([
                'event_name' => $data['event_name'] ?? $event->event_name,
                'description' => $data['description'] ?? $event->description,
                'max_individuals' => $data['max_individuals'] ?? $event->max_individuals,
                'gender_type' => $data['gender_type'] ?? $event->gender_type,
                'hospitality_available' => $data['hospitality_available'] ?? $event->hospitality_available,
                'pricing_type' => $data['pricing_type'] ?? $event->pricing_type,
                'base_price' => $data['base_price'] ?? $event->base_price,
                'discount_price' => $data['discount_price'] ?? $event->discount_price,
                'prices_by_age' => $data['prices_by_age'] ?? $event->prices_by_age,
                'cancellation_policy' => $data['cancellation_policy'] ?? $event->cancellation_policy,
                'meeting_point' => $data['meeting_point'] ?? $event->meeting_point,
                'start_at' => $data['start_at'] ?? $event->start_at,
                'end_at' => $data['end_at'] ?? $event->end_at,
                'age_min' => $data['age_min'] ?? $event->age_min,
                'age_max' => $data['age_max'] ?? $event->age_max,
                'price_per_person' => $data['price_per_person'] ?? $event->price_per_person,
            ]);

            return $event->load(['service.user', 'service']);
        });
    }

    /**
     * Delete an event and related data
     *
     * @param Event $event
     * @return bool
     */
    public function deleteEvent(Event $event): bool
    {
        return DB::transaction(function () use ($event) {
            // Soft delete bookings
            $event->bookings()->delete();
            
            // Soft delete reviews
            $event->reviews()->delete();
            
            // Soft delete event
            $event->delete();
            
            // Soft delete service
            $event->service->delete();
            
            return true;
        });
    }

    /**
     * Get event statistics
     *
     * @param Event $event
     * @return array
     */
    public function getEventStats(Event $event): array
    {
        $bookings = $event->bookings();
        $reviews = $event->reviews();

        return [
            'total_bookings' => $bookings->count(),
            'confirmed_bookings' => $bookings->where('status', 'confirmed')->count(),
            'pending_bookings' => $bookings->where('status', 'pending')->count(),
            'cancelled_bookings' => $bookings->where('status', 'cancelled')->count(),
            'total_revenue' => $bookings->where('status', 'confirmed')->sum('total'),
            'average_rating' => $reviews->where('reviews.is_approved', true)->avg('rating') ?? 0,
            'total_reviews' => $reviews->count(),
            'approved_reviews' => $reviews->where('reviews.is_approved', true)->count(),
            'pending_reviews' => $reviews->where('reviews.is_approved', false)->count(),
            'tickets_sold' => $event->tickets_sold,
            'tickets_available' => $event->tickets_available,
            'attendance_rate' => $event->max_individuals > 0 ? 
                round(($event->tickets_sold / $event->max_individuals) * 100, 2) : 0,
        ];
    }

    /**
     * Get events with filters and pagination
     *
     * @param array $filters
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getEventsWithFilters(array $filters = [], int $perPage = 15)
    {
        $query = Event::with(['service.user', 'service'])
            ->withCount(['bookings', 'reviews']);

        // Apply filters
        if (!empty($filters['search'])) {
            $query->where(function($q) use ($filters) {
                $q->where('event_name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%')
                  ->orWhereHas('service.user', function($subQ) use ($filters) {
                      $subQ->where('full_name', 'like', '%' . $filters['search'] . '%');
                  });
            });
        }

        if (!empty($filters['status'])) {
            switch ($filters['status']) {
                case 'upcoming':
                    $query->upcoming();
                    break;
                case 'ongoing':
                    $query->ongoing();
                    break;
                case 'completed':
                    $query->completed();
                    break;
            }
        }

        if (!empty($filters['type'])) {
            $query->byType($filters['type']);
        }

        if (!empty($filters['city'])) {
            $query->whereHas('service.user.companyProfile', function($q) use ($filters) {
                $q->where('city_id', $filters['city']);
            });
        }

        if (!empty($filters['date_from'])) {
            $query->where('start_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('start_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('start_at', 'desc')->paginate($perPage);
    }

    /**
     * Upload media files for an event
     *
     * @param Event $event
     * @param UploadedFile $file
     * @param string $collection
     * @return \Spatie\MediaLibrary\MediaCollections\Models\Media
     */
    public function uploadEventMedia(Event $event, UploadedFile $file, string $collection = 'event_images')
    {
        return $event->addMediaFromRequest($file)
            ->toMediaCollection($collection);
    }

    /**
     * Get event attendees with details
     *
     * @param Event $event
     * @return Collection
     */
    public function getEventAttendees(Event $event): Collection
    {
        return $event->bookings()
            ->with(['user', 'user.customerProfile'])
            ->where('status', 'confirmed')
            ->get()
            ->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'user_id' => $booking->user_id,
                    'user_name' => $booking->user->full_name,
                    'user_email' => $booking->user->email,
                    'user_phone' => $booking->user->phone ?? 'غير محدد',
                    'booking_date' => $booking->created_at,
                    'total_amount' => $booking->total,
                    'status' => $booking->status,
                ];
            });
    }

    /**
     * Get user-generated content for an event
     *
     * @param Event $event
     * @return Collection
     */
    public function getEventUserContent(Event $event): Collection
    {
        return $event->reviews()
            ->with(['user'])
            ->whereNotNull('comment')
            ->get()
            ->map(function ($review) {
                return [
                    'id' => $review->id,
                    'user_id' => $review->user_id,
                    'user_name' => $review->user->full_name,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'images' => $review->getMedia('review_images')->map(fn($m) => $m->getUrl()),
                    'is_approved' => $review->is_approved,
                    'created_at' => $review->created_at,
                ];
            });
    }

    /**
     * Approve or reject user content
     *
     * @param Review $review
     * @param bool $approved
     * @param string|null $notes
     * @return Review
     */
    public function moderateUserContent(Review $review, bool $approved, ?string $notes = null): Review
    {
        $review->update([
            'is_approved' => $approved,
            'approved_at' => $approved ? now() : null,
            'approval_notes' => $notes,
        ]);

        return $review;
    }

    /**
     * Get dashboard statistics for events
     *
     * @return array
     */
    public function getDashboardStats(): array
    {
        $totalEvents = Event::count();
        $upcomingEvents = Event::upcoming()->count();
        $ongoingEvents = Event::ongoing()->count();
        $completedEvents = Event::completed()->count();
        
        $totalBookings = Booking::whereHas('service', function($q) {
            $q->whereHas('event');
        })->count();
        
        $totalRevenue = Booking::whereHas('service', function($q) {
            $q->whereHas('event');
        })->where('status', 'confirmed')->sum('total');

        return [
            'total_events' => $totalEvents,
            'upcoming_events' => $upcomingEvents,
            'ongoing_events' => $ongoingEvents,
            'completed_events' => $completedEvents,
            'total_bookings' => $totalBookings,
            'total_revenue' => $totalRevenue,
        ];
    }
}
